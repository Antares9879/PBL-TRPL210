<?php

namespace App\Http\Controllers\Api\UserDepartemen;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserDepartemen\ValidasiLemburRequest;
use App\Models\AuditLog;
use App\Models\Karyawan;
use App\Models\PengajuanLembur;
use App\Services\AuditLogService;
use App\Services\LemburService;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ValidasiLemburApiController — F12
 *
 * Memungkinkan User Departemen menyetujui atau menolak pengajuan
 * lembur karyawan outsource yang bekerja di departemennya.
 *
 * Scope keamanan:
 *   - User Departemen hanya bisa memproses lembur karyawan
 *     yang ber-departemen sama dengan profil User Departemen yang login.
 *   - Validasi scope dilakukan via findLembur() helper.
 *
 * Business rules (sesuai SKPPL 1.3, UC-12):
 *   - Hanya pengajuan berstatus 'menunggu' yang bisa diproses.
 *   - Saat approve: menit_lembur_resmi dihitung via LemburService
 *     (min antara menit_diajukan dan menit_kelebihan aktual di absensi).
 *   - Saat reject: wajib isi catatan_penolakan, status → ditolak.
 *   - Setiap aksi dicatat di audit_log dan kirim notifikasi ke karyawan.
 *
 * Endpoints:
 *   GET  /api/departemen/validasi-lembur               → index()
 *   GET  /api/departemen/validasi-lembur/{id}          → show()
 *   POST /api/departemen/validasi-lembur/{id}/proses   → proses()
 */
class ValidasiLemburApiController extends Controller
{
    public function __construct(
        private readonly LemburService $lemburService,
    ) {}

    // ════════════════════════════════════════════════════════════════════════
    //  INDEX — Daftar pengajuan lembur di departemen User yang login
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Daftar pengajuan lembur yang perlu diproses oleh User Departemen.
     *
     * Default: tampilkan yang berstatus 'menunggu'.
     * Filter opsional: status, tanggal_dari, tanggal_sampai, nama karyawan.
     * Paginasi: 20 per halaman (data bisa tumbuh sesuai panduan STRUKTUR-FOLDER.md).
     */
    public function index(Request $request): JsonResponse
    {
        $idDepartemen = $this->getIdDepartemen();

        $query = PengajuanLembur::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen,id_perusahaan',
            'karyawan.departemen:id_departemen,nama_departemen,kode_departemen',
            'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
            'absensi:id_absensi,id_karyawan,tanggal_absensi,waktu_check_in,waktu_check_out,menit_kelebihan,menit_kerja_normal',
        ])
        ->whereHas('karyawan', fn ($q) => $q->where('id_departemen', $idDepartemen));

        // Filter status — default 'menunggu'
        $status = $request->get('status', PengajuanLembur::STATUS_MENUNGGU);
        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        // Filter tanggal lembur
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal_lembur', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_lembur', '<=', $request->tanggal_sampai);
        }

        // Filter nama / nomor karyawan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', fn ($q) => $q
                ->where('nama_lengkap', 'like', "%{$search}%")
                ->orWhere('nomor_karyawan', 'like', "%{$search}%")
            );
        }

        $data = $query
            ->orderByRaw("FIELD(status, 'menunggu', 'disetujui', 'ditolak', 'kadaluarsa')")
            ->orderByDesc('tanggal_lembur')
            ->paginate(20);

        $data->getCollection()->transform(fn ($l) => $this->formatLembur($l));

        return response()->json([
            'status'  => true,
            'message' => 'Data pengajuan lembur berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SHOW — Detail satu pengajuan lembur
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Detail lengkap satu pengajuan lembur.
     * Scope ke departemen User Departemen yang login.
     */
    public function show(int $id): JsonResponse
    {
        $lembur = $this->findLembur($id);

        if (! $lembur) {
            return $this->notFound();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail pengajuan lembur berhasil dimuat.',
            'data'    => $this->formatLembur($lembur, detail: true),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PROSES — Approve atau Reject pengajuan lembur
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Proses satu pengajuan lembur: approve atau reject.
     *
     * Alur approve:
     *   1. Validasi status masih 'menunggu'.
     *   2. Hitung menit_lembur_resmi via LemburService::hitungMenitResmi()
     *      (min antara menit_diajukan dan menit_kelebihan aktual di absensi).
     *   3. Update status pengajuan_lembur → 'disetujui'.
     *   4. Catat audit_log.
     *   5. Kirim notifikasi ke karyawan via NotifikasiService.
     *
     * Alur reject:
     *   1. Validasi status masih 'menunggu', wajib ada catatan_penolakan.
     *   2. Update status → 'ditolak', simpan catatan.
     *   3. Catat audit_log.
     *   4. Kirim notifikasi ke karyawan.
     */
    public function proses(ValidasiLemburRequest $request, int $id): JsonResponse
    {
        $userDepartemen = auth()->user();
        $lembur         = $this->findLembur($id);

        if (! $lembur) {
            return $this->notFound();
        }

        // Guard: hanya bisa proses yang masih 'menunggu'
        if ($lembur->status !== PengajuanLembur::STATUS_MENUNGGU) {
            return response()->json([
                'status'  => false,
                'message' => 'Pengajuan lembur ini sudah diproses sebelumnya.',
                'data'    => [
                    'status_saat_ini' => $lembur->status,
                    'waktu_proses'    => $lembur->waktu_proses?->toDateTimeString(),
                ],
            ], 422);
        }

        $aksi    = $request->aksi;
        $sebelum = $lembur->toArray();

        if ($aksi === 'approve') {
            $this->prosesApprove($lembur, $userDepartemen);
        } else {
            $this->prosesReject($lembur, $userDepartemen, $request->catatan_penolakan);
        }

        // Audit log
        $auditAksi = $aksi === 'approve' ? AuditLog::AKSI_APPROVE : AuditLog::AKSI_REJECT;
        AuditLogService::catat(
            pengguna:    $userDepartemen,
            jenis:       AuditLog::JENIS_LEMBUR,
            idReferensi: $lembur->id_lembur,
            aksi:        $auditAksi,
            catatan:     $aksi === 'reject' ? $request->catatan_penolakan : null,
            sebelum:     $sebelum,
            sesudah:     $lembur->fresh()->toArray(),
        );

        // Notifikasi ke karyawan
        $this->kirimNotifikasiKaryawan(
            lembur:      $lembur,
            aksi:        $aksi,
            catatan:     $request->catatan_penolakan,
            idPengirim:  $userDepartemen->id_pengguna,
        );

        $lembur->refresh()->load([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen',
            'karyawan.departemen:id_departemen,nama_departemen',
            'absensi:id_absensi,tanggal_absensi,menit_kelebihan',
        ]);

        $pesan = $aksi === 'approve'
            ? "Pengajuan lembur {$lembur->karyawan->nama_lengkap} berhasil disetujui. Menit lembur resmi: {$lembur->menit_lembur_resmi} menit."
            : "Pengajuan lembur {$lembur->karyawan->nama_lengkap} berhasil ditolak.";

        Log::info('Lembur diproses oleh User Departemen', [
            'id_lembur'       => $lembur->id_lembur,
            'aksi'            => $aksi,
            'diproses_oleh'   => $userDepartemen->id_pengguna,
            'menit_resmi'     => $lembur->menit_lembur_resmi,
        ]);

        return response()->json([
            'status'  => true,
            'message' => $pesan,
            'data'    => $this->formatLembur($lembur, detail: true),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Business logic
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Proses approve: hitung menit resmi dan update status.
     */
    private function prosesApprove(PengajuanLembur $lembur, \App\Models\Pengguna $pengguna): void
    {
        // Muat relasi absensi jika belum dimuat
        $lembur->loadMissing('absensi');

        $menitResmi = $this->lemburService->hitungMenitResmi(
            absensi:       $lembur->absensi,
            menitDiajukan: $lembur->menit_lembur_diajukan,
        );

        $lembur->update([
            'status'               => PengajuanLembur::STATUS_DISETUJUI,
            'menit_lembur_resmi'   => $menitResmi,
            'catatan_penolakan'    => null,
            'diproses_oleh'        => $pengguna->id_pengguna,
            'waktu_proses'         => now(),
        ]);
    }

    /**
     * Proses reject: simpan catatan dan update status.
     */
    private function prosesReject(
        PengajuanLembur $lembur,
        \App\Models\Pengguna $pengguna,
        string $catatanPenolakan,
    ): void {
        $lembur->update([
            'status'             => PengajuanLembur::STATUS_DITOLAK,
            'menit_lembur_resmi' => 0,
            'catatan_penolakan'  => $catatanPenolakan,
            'diproses_oleh'      => $pengguna->id_pengguna,
            'waktu_proses'       => now(),
        ]);
    }

    /**
     * Kirim notifikasi ke karyawan via NotifikasiService (tulis ke tabel notifikasi).
     */
    private function kirimNotifikasiKaryawan(
        PengajuanLembur $lembur,
        string $aksi,
        ?string $catatan,
        int $idPengirim,
    ): void {
        $lembur->loadMissing('karyawan.pengguna');
        $idKaryawan = $lembur->karyawan?->pengguna?->id_pengguna;

        if (! $idKaryawan) {
            Log::warning('Notifikasi lembur gagal: id_pengguna karyawan tidak ditemukan', [
                'id_lembur' => $lembur->id_lembur,
            ]);
            return;
        }

        $tanggal = $lembur->tanggal_lembur?->format('d M Y') ?? '-';

        if ($aksi === 'approve') {
            $judul = "Pengajuan lembur {$tanggal} Anda disetujui";
            $isi   = "Pengajuan lembur Anda pada {$tanggal} telah disetujui oleh User Departemen. "
                   . "Menit lembur resmi yang dicatat: {$lembur->menit_lembur_resmi} menit.";
        } else {
            $judul = "Pengajuan lembur {$tanggal} Anda ditolak";
            $isi   = "Pengajuan lembur Anda pada {$tanggal} ditolak."
                   . ($catatan ? " Alasan: {$catatan}" : '');
        }

        NotifikasiService::kirim(
            idPenerima:  $idKaryawan,
            judul:       $judul,
            isi:         $isi,
            jenis:       \App\Models\Notifikasi::JENIS_LEMBUR,
            idPengirim:  $idPengirim,
            idReferensi: $lembur->id_lembur,
        );
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Helpers
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Ambil id_departemen User Departemen yang sedang login.
     */
    private function getIdDepartemen(): int
    {
        return auth()->user()->userDepartemenProfile->id_departemen;
    }

    /**
     * Cari pengajuan lembur berdasarkan ID + scope departemen.
     * Memastikan User Departemen tidak bisa mengakses lembur dari departemen lain.
     */
    private function findLembur(int $id): ?PengajuanLembur
    {
        return PengajuanLembur::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen,id_perusahaan,id_pengguna',
            'karyawan.departemen:id_departemen,nama_departemen,kode_departemen',
            'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
            'karyawan.pengguna:id_pengguna,email',
            'absensi:id_absensi,id_karyawan,tanggal_absensi,waktu_check_in,waktu_check_out,menit_kelebihan,menit_kerja_normal,menit_telat',
            'prosesor:id_pengguna,nama_lengkap',
        ])
        ->whereHas('karyawan', fn ($q) => $q->where('id_departemen', $this->getIdDepartemen()))
        ->find($id);
    }

    /**
     * Format output pengajuan lembur.
     * Mode detail menambahkan data absensi referensi yang lebih lengkap.
     */
    private function formatLembur(PengajuanLembur $l, bool $detail = false): array
    {
        $base = [
            'id_lembur'             => $l->id_lembur,
            'tanggal_lembur'        => $l->tanggal_lembur?->format('Y-m-d'),
            'jam_mulai_estimasi'    => $l->jam_mulai_estimasi,
            'jam_selesai_estimasi'  => $l->jam_selesai_estimasi,
            'menit_lembur_diajukan' => $l->menit_lembur_diajukan,
            'menit_lembur_resmi'    => $l->menit_lembur_resmi,
            'alasan_lembur'         => $l->alasan_lembur,
            'status'                => $l->status,
            'catatan_penolakan'     => $l->catatan_penolakan,
            'batas_pengajuan'       => $l->batas_pengajuan?->format('Y-m-d'),
            'diajukan_pada'         => $l->diajukan_pada?->toDateTimeString(),
            'waktu_proses'          => $l->waktu_proses?->toDateTimeString(),
            'diproses_oleh'         => $l->prosesor?->nama_lengkap,
            'karyawan'              => $l->karyawan ? [
                'id_karyawan'    => $l->karyawan->id_karyawan,
                'nama_lengkap'   => $l->karyawan->nama_lengkap,
                'nomor_karyawan' => $l->karyawan->nomor_karyawan,
                'departemen'     => $l->karyawan->departemen ? [
                    'nama_departemen' => $l->karyawan->departemen->nama_departemen,
                    'kode_departemen' => $l->karyawan->departemen->kode_departemen,
                ] : null,
                'perusahaan'     => $l->karyawan->perusahaan?->nama_perusahaan,
            ] : null,
        ];

        if ($detail && $l->absensi) {
            $base['absensi_referensi'] = [
                'tanggal_absensi'    => $l->absensi->tanggal_absensi?->format('Y-m-d'),
                'waktu_check_in'     => $l->absensi->waktu_check_in?->format('H:i'),
                'waktu_check_out'    => $l->absensi->waktu_check_out?->format('H:i'),
                'menit_kerja_normal' => $l->absensi->menit_kerja_normal,
                'menit_telat'        => $l->absensi->menit_telat,
                'menit_kelebihan'    => $l->absensi->menit_kelebihan,
            ];
        }

        return $base;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => 'Pengajuan lembur tidak ditemukan.',
            'data'    => null,
        ], 404);
    }
}