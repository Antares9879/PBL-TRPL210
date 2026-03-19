<?php

namespace App\Http\Controllers\Api\AdminOutsource;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOutsource\ValidasiAbsensiRequest;
use App\Http\Requests\AdminOutsource\ValidasiIzinRequest;
use App\Models\Absensi;
use App\Models\PengajuanIzin;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ValidasiAbsensiApiController — F10, F11
 *
 * F10: Approve/Reject data kehadiran dan pengajuan izin karyawan.
 * F11: Pantau rekap status absensi seluruh karyawan yang dikelola.
 *
 * Scope: hanya karyawan dari perusahaan Admin yang login.
 *
 * Endpoints:
 *   GET  /api/admin/validasi-absensi         → index()    F11 — daftar absensi
 *   POST /api/admin/validasi-absensi/{id}    → validasi() F10 — approve/reject absensi
 *   GET  /api/admin/validasi-absensi/izin    → indexIzin()      — daftar izin pending
 *   POST /api/admin/validasi-absensi/izin/{id} → validasiIzin() — approve/reject izin
 */
class ValidasiAbsensiApiController extends Controller
{
    private function getIdPerusahaan(): int
    {
        return auth()->user()->adminOutsourceProfile->id_perusahaan;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  F11 — PANTAU STATUS ABSENSI
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Daftar absensi seluruh karyawan perusahaan Admin.
     * Bisa difilter: status_validasi, status_kehadiran, tanggal, nama karyawan.
     */
    public function index(Request $request): JsonResponse
    {
        $idPerusahaan = $this->getIdPerusahaan();

        $query = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_perusahaan',
            'jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang',
        ])
        ->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $idPerusahaan));

        // Filter status validasi (default: tampilkan semua)
        if ($request->filled('status_validasi')) {
            $query->where('status_validasi', $request->status_validasi);
        }

        if ($request->filled('status_kehadiran')) {
            $query->where('status_kehadiran', $request->status_kehadiran);
        }

        // Filter tanggal
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal_absensi', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_absensi', '<=', $request->tanggal_sampai);
        }

        // Filter hari ini shortcut
        if ($request->boolean('hari_ini')) {
            $query->whereDate('tanggal_absensi', today());
        }

        // Search nama karyawan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', fn($q) => $q
                ->where('nama_lengkap', 'like', "%{$search}%")
                ->orWhere('nomor_karyawan', 'like', "%{$search}%")
            );
        }

        $data = $query
            ->orderByDesc('tanggal_absensi')
            ->orderBy('status_validasi') // menunggu duluan
            ->paginate(20);

        $data->getCollection()->transform(fn($a) => $this->formatAbsensi($a));

        return response()->json([
            'status'  => true,
            'message' => 'Data absensi berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  F10 — VALIDASI KEHADIRAN
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Approve atau reject satu data absensi.
     *
     * Business rules:
     *   - Hanya absensi dengan status_validasi = 'menunggu' yang bisa diproses.
     *   - Saat approve: status_kehadiran di-set ke 'hadir', menit_telat tercatat.
     *   - Saat reject: status_kehadiran di-set ke 'alpa', wajib isi catatan.
     *   - Setiap aksi dicatat di audit_log dan kirim notifikasi ke karyawan.
     */
    public function validasi(ValidasiAbsensiRequest $request, int $id): JsonResponse
    {
        $admin = auth()->user();

        $absensi = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,id_pengguna,id_perusahaan',
        ])
        ->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $this->getIdPerusahaan()))
        ->find($id);

        if (! $absensi) {
            return response()->json([
                'status'  => false,
                'message' => 'Data absensi tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if ($absensi->status_validasi !== Absensi::VALIDASI_MENUNGGU) {
            return response()->json([
                'status'  => false,
                'message' => 'Absensi ini sudah diproses sebelumnya.',
                'data'    => ['status_validasi' => $absensi->status_validasi],
            ], 422);
        }

        $sebelum = $absensi->toArray();
        $aksi    = $request->aksi;

        if ($aksi === 'approve') {
            $absensi->update([
                'status_validasi'  => Absensi::VALIDASI_DISETUJUI,
                'status_kehadiran' => Absensi::STATUS_HADIR,
                'divalidasi_oleh'  => $admin->id_pengguna,
                'waktu_validasi'   => now(),
                'catatan_penolakan'=> null,
            ]);
        } else {
            $absensi->update([
                'status_validasi'  => Absensi::VALIDASI_DITOLAK,
                'status_kehadiran' => Absensi::STATUS_ALPA,
                'divalidasi_oleh'  => $admin->id_pengguna,
                'waktu_validasi'   => now(),
                'catatan_penolakan'=> $request->catatan_penolakan,
            ]);
        }

        // Audit log
        $auditAksi = $aksi === 'approve' ? AuditLog::AKSI_APPROVE : AuditLog::AKSI_REJECT;
        AuditLogService::catat(
            pengguna:    $admin,
            jenis:       AuditLog::JENIS_ABSENSI,
            idReferensi: $absensi->id_absensi,
            aksi:        $auditAksi,
            catatan:     $request->catatan_penolakan,
            sebelum:     $sebelum,
            sesudah:     $absensi->fresh()->toArray(),
        );

        // Notifikasi ke karyawan
        NotifikasiService::absensiDivalidasi(
            idKaryawan:  $absensi->karyawan->id_pengguna,
            statusBaru:  $aksi === 'approve' ? 'disetujui' : 'ditolak',
            tanggal:     $absensi->tanggal_absensi->format('d M Y'),
            catatan:     $request->catatan_penolakan,
            idAbsensi:   $absensi->id_absensi,
            idPengirim:  $admin->id_pengguna,
        );

        $absensi->refresh();
        $pesan = $aksi === 'approve'
            ? 'Absensi berhasil disetujui.'
            : 'Absensi berhasil ditolak.';

        return response()->json([
            'status'  => true,
            'message' => $pesan,
            'data'    => $this->formatAbsensi($absensi),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  F10 — VALIDASI IZIN
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Daftar pengajuan izin yang menunggu validasi Admin Outsource.
     */
    public function indexIzin(Request $request): JsonResponse
    {
        $idPerusahaan = $this->getIdPerusahaan();

        $query = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen:id_dokumen,id_izin,nama_file,tipe_file,ukuran_kb,diunggah_pada',
        ])
        ->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $idPerusahaan));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: tampilkan yang menunggu
            $query->where('status', PengajuanIzin::STATUS_MENUNGGU);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', fn($q) => $q->where('nama_lengkap', 'like', "%{$search}%"));
        }

        $data = $query
            ->orderByDesc('diajukan_pada')
            ->paginate(20);

        $data->getCollection()->transform(fn($i) => $this->formatIzin($i));

        return response()->json([
            'status'  => true,
            'message' => 'Data pengajuan izin berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    /**
     * Approve atau reject pengajuan izin karyawan.
     *
     * Saat approve:
     *   - status pengajuan_izin → disetujui
     *   - status_kehadiran di baris absensi tanggal izin (jika ada) → izin
     * Saat reject:
     *   - status pengajuan_izin → ditolak, wajib catatan
     */
    public function validasiIzin(ValidasiIzinRequest $request, int $id): JsonResponse
    {
        $admin = auth()->user();

        $izin = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,id_pengguna,id_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen',
        ])
        ->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $this->getIdPerusahaan()))
        ->find($id);

        if (! $izin) {
            return response()->json([
                'status'  => false,
                'message' => 'Pengajuan izin tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if ($izin->status !== PengajuanIzin::STATUS_MENUNGGU) {
            return response()->json([
                'status'  => false,
                'message' => 'Pengajuan izin ini sudah diproses sebelumnya.',
                'data'    => ['status' => $izin->status],
            ], 422);
        }

        // Validasi dokumen wajib: jika jenis izin wajib_dokumen & belum upload → tolak approve
        if ($request->aksi === 'approve' && $izin->jenisIzin->wajib_dokumen && $izin->dokumen->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'Tidak dapat menyetujui izin ini. Dokumen pendukung wajib diunggah terlebih dahulu.',
                'data'    => null,
            ], 422);
        }

        $sebelum = $izin->toArray();
        $aksi    = $request->aksi;

        if ($aksi === 'approve') {
            $izin->update([
                'status'               => PengajuanIzin::STATUS_DISETUJUI,
                'divalidasi_admin'     => $admin->id_pengguna,
                'waktu_validasi_admin' => now(),
                'catatan_penolakan'    => null,
            ]);

            // Tandai baris absensi hari itu sebagai 'izin' jika sudah ada
            Absensi::where('id_karyawan', $izin->id_karyawan)
                ->whereDate('tanggal_absensi', $izin->tanggal_izin)
                ->update(['status_kehadiran' => Absensi::STATUS_IZIN]);

        } else {
            $izin->update([
                'status'               => PengajuanIzin::STATUS_DITOLAK,
                'divalidasi_admin'     => $admin->id_pengguna,
                'waktu_validasi_admin' => now(),
                'catatan_penolakan'    => $request->catatan_penolakan,
            ]);
        }

        // Audit log
        $auditAksi = $aksi === 'approve' ? AuditLog::AKSI_APPROVE : AuditLog::AKSI_REJECT;
        AuditLogService::catat(
            pengguna:    $admin,
            jenis:       AuditLog::JENIS_IZIN,
            idReferensi: $izin->id_izin,
            aksi:        $auditAksi,
            catatan:     $request->catatan_penolakan,
            sebelum:     $sebelum,
            sesudah:     $izin->fresh()->toArray(),
        );

        // Notifikasi ke karyawan
        NotifikasiService::izinDiproses(
            idKaryawan:  $izin->karyawan->id_pengguna,
            statusBaru:  $aksi === 'approve' ? 'disetujui' : 'ditolak',
            catatan:     $request->catatan_penolakan,
            idIzin:      $izin->id_izin,
            idPengirim:  $admin->id_pengguna,
        );

        $pesan = $aksi === 'approve'
            ? 'Pengajuan izin berhasil disetujui.'
            : 'Pengajuan izin berhasil ditolak.';

        return response()->json([
            'status'  => true,
            'message' => $pesan,
            'data'    => $this->formatIzin($izin->fresh()->load(['karyawan', 'jenisIzin', 'dokumen'])),
        ]);
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────

    private function formatAbsensi(Absensi $a): array
    {
        return [
            'id_absensi'         => $a->id_absensi,
            'tanggal_absensi'    => $a->tanggal_absensi?->format('Y-m-d'),
            'karyawan'           => $a->karyawan ? [
                'id_karyawan'   => $a->karyawan->id_karyawan,
                'nama_lengkap'  => $a->karyawan->nama_lengkap,
                'nomor_karyawan'=> $a->karyawan->nomor_karyawan,
            ] : null,
            'shift'              => $a->jadwal?->shift ? [
                'nama_shift' => $a->jadwal->shift->nama_shift,
                'jam_masuk'  => substr($a->jadwal->shift->jam_masuk, 0, 5),
                'jam_pulang' => substr($a->jadwal->shift->jam_pulang, 0, 5),
            ] : null,
            'waktu_check_in'     => $a->waktu_check_in?->format('H:i'),
            'waktu_check_out'    => $a->waktu_check_out?->format('H:i'),
            'is_lokasi_valid_in' => $a->is_lokasi_valid_in,
            'is_lokasi_valid_out'=> $a->is_lokasi_valid_out,
            'menit_kerja_normal' => $a->menit_kerja_normal,
            'menit_telat'        => $a->menit_telat,
            'menit_pulang_cepat' => $a->menit_pulang_cepat,
            'menit_kelebihan'    => $a->menit_kelebihan,
            'status_kehadiran'   => $a->status_kehadiran,
            'status_validasi'    => $a->status_validasi,
            'catatan_penolakan'  => $a->catatan_penolakan,
            'waktu_validasi'     => $a->waktu_validasi?->toDateTimeString(),
        ];
    }

    private function formatIzin(PengajuanIzin $i): array
    {
        return [
            'id_izin'              => $i->id_izin,
            'tanggal_izin'         => $i->tanggal_izin?->format('Y-m-d'),
            'karyawan'             => $i->karyawan ? [
                'id_karyawan'  => $i->karyawan->id_karyawan,
                'nama_lengkap' => $i->karyawan->nama_lengkap,
            ] : null,
            'jenis_izin'           => $i->jenisIzin ? [
                'nama_jenis'    => $i->jenisIzin->nama_jenis,
                'wajib_dokumen' => $i->jenisIzin->wajib_dokumen,
            ] : null,
            'keterangan'           => $i->keterangan,
            'status'               => $i->status,
            'catatan_penolakan'    => $i->catatan_penolakan,
            'status_dokumen'       => $i->status_dokumen,
            'jumlah_dokumen'       => $i->dokumen?->count() ?? 0,
            'diajukan_pada'        => $i->diajukan_pada?->toDateTimeString(),
            'waktu_validasi_admin' => $i->waktu_validasi_admin?->toDateTimeString(),
        ];
    }
}