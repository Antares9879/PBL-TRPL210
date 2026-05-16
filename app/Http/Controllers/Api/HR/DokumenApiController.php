<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\VerifikasiDokumenRequest;
use App\Models\AuditLog;
use App\Models\DokumenIzin;
use App\Models\Notifikasi;
use App\Models\Pengguna;
use App\Models\PengajuanIzin;
use App\Services\AuditLogService;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * DokumenApiController — HR Ecogreen
 *
 * Memungkinkan HR memverifikasi kelengkapan dokumen administrasi pengajuan izin.
 * HR menentukan apakah dokumen yang diunggah Admin Outsource sudah lengkap atau tidak
 * sebelum rekap absensi dapat ditetapkan sebagai Final.
 *
 * Business rules (UC-14):
 *   - HR memeriksa dokumen per pengajuan izin.
 *   - Aksi "Tandai Lengkap" → status_dokumen = 'lengkap'.
 *   - Aksi "Tandai Tidak Lengkap" → status_dokumen = 'tidak_lengkap' + notifikasi ke Admin Outsource.
 *   - Rekap hanya bisa ditetapkan Final jika semua dokumen berstatus 'lengkap'.
 *
 * Endpoints:
 *   GET  /api/hr/dokumen                     → index()         — daftar pengajuan izin beserta status dokumen
 *   GET  /api/hr/dokumen/{id}                → show()          — detail satu pengajuan
 *   POST /api/hr/dokumen/{id}/verifikasi     → verifikasi()    — tandai lengkap / tidak lengkap
 *   GET  /api/hr/dokumen/{id}/stream/{docId} → streamDokumen() — preview file dokumen
 */
class DokumenApiController extends Controller
{
    // ════════════════════════════════════════════════════════════════════════
    //  INDEX — Daftar pengajuan izin beserta status dokumen
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/dokumen
     *
     * Mengembalikan daftar pengajuan izin yang sudah divalidasi Admin Outsource,
     * lengkap dengan status kelengkapan dokumen per pengajuan.
     *
     * Filter: status_dokumen, id_departemen, id_perusahaan, bulan, tahun, search
     * Default: menampilkan semua status, urutkan by dokumen belum lengkap dulu
     */
    public function index(Request $request): JsonResponse
    {
        $query = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen,id_perusahaan',
            'karyawan.departemen:id_departemen,nama_departemen',
            'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen:id_dokumen,id_izin,nama_file,tipe_file,ukuran_kb,diunggah_pada',
        ])
        // HR hanya melihat yang sudah disetujui Admin — sudah melewati validasi awal
        ->where('status', PengajuanIzin::STATUS_DISETUJUI);

        // Filter status dokumen
        if ($request->filled('status_dokumen')) {
            $query->where('status_dokumen', $request->status_dokumen);
        }

        // Filter departemen
        if ($request->filled('id_departemen')) {
            $query->whereHas('karyawan', fn($q) => $q->where('id_departemen', $request->id_departemen));
        }

        // Filter perusahaan outsource
        if ($request->filled('id_perusahaan')) {
            $query->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $request->id_perusahaan));
        }

        // Filter periode
        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal_izin', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_izin', $request->tahun);
        }

        // Search nama karyawan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', fn($q) => $q->where('nama_lengkap', 'like', "%{$search}%"));
        }

        // Urutkan: belum lengkap & sudah upload duluan, baru yang sudah lengkap
        $query->orderByRaw("FIELD(status_dokumen, 'sudah_upload', 'tidak_lengkap', 'belum_upload', 'lengkap')")
              ->orderByDesc('diajukan_pada');

        $data = $query->paginate(20);
        $data->getCollection()->transform(fn($i) => $this->formatIzin($i));

        return response()->json([
            'status'  => true,
            'message' => 'Daftar pengajuan izin berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SHOW — Detail satu pengajuan izin
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/dokumen/{id}
     *
     * Detail lengkap satu pengajuan izin termasuk semua dokumen yang diunggah.
     */
    public function show(int $id): JsonResponse
    {
        $izin = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,posisi,id_departemen,id_perusahaan',
            'karyawan.departemen:id_departemen,nama_departemen,kode_departemen',
            'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen,keterangan',
            'dokumen:id_dokumen,id_izin,nama_file,tipe_file,ukuran_kb,diunggah_pada',
            'validatorAdmin:id_pengguna,nama_lengkap',
        ])
        ->where('status', PengajuanIzin::STATUS_DISETUJUI)
        ->find($id);

        if (! $izin) {
            return response()->json([
                'status'  => false,
                'message' => 'Pengajuan izin tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail pengajuan izin berhasil dimuat.',
            'data'    => $this->formatIzin($izin, detail: true),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  VERIFIKASI — Tandai lengkap atau tidak lengkap
    // ════════════════════════════════════════════════════════════════════════

    /**
     * POST /api/hr/dokumen/{id}/verifikasi
     *
     * HR menandai dokumen sebagai lengkap atau tidak lengkap.
     *
     * Alur "tandai_lengkap":
     *   1. Update status_dokumen → 'lengkap'.
     *   2. Hapus catatan_dokumen jika ada.
     *   3. Catat audit log.
     *
     * Alur "tandai_tidak_lengkap":
     *   1. Update status_dokumen → 'tidak_lengkap'.
     *   2. Simpan catatan_dokumen berisi kekurangan.
     *   3. Kirim notifikasi ke Admin Outsource perusahaan karyawan.
     *   4. Catat audit log.
     */
    public function verifikasi(VerifikasiDokumenRequest $request, int $id): JsonResponse
    {
        $hr = Auth::user();
        if (! $hr instanceof Pengguna) {
            return response()->json([
                'status'  => false,
                'message' => 'Pengguna tidak terautentikasi.',
                'data'    => null,
            ], 401);
        }

        $izin = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,id_pengguna,id_perusahaan',
            'karyawan.perusahaan.adminProfiles.pengguna:id_pengguna',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen',
        ])
        ->where('status', PengajuanIzin::STATUS_DISETUJUI)
        ->find($id);

        if (! $izin) {
            return response()->json([
                'status'  => false,
                'message' => 'Pengajuan izin tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $aksi    = $request->aksi;
        $sebelum = $izin->toArray();

        if ($aksi === 'tandai_lengkap') {
            // Guard: wajib_dokumen tapi tidak ada dokumen yang diupload
            if ($izin->jenisIzin->wajib_dokumen && $izin->dokumen->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tidak dapat menandai lengkap. Jenis izin ini wajib memiliki dokumen pendukung yang diunggah.',
                    'data'    => null,
                ], 422);
            }

            $izin->update([
                'status_dokumen'  => PengajuanIzin::DOKUMEN_LENGKAP,
                'catatan_dokumen' => null,
                'diverifikasi_hr' => $hr->id_pengguna,
                'waktu_verifikasi_hr' => now(),
            ]);

            $pesan = "Dokumen pengajuan izin {$izin->karyawan->nama_lengkap} berhasil ditandai Lengkap.";

        } else {
            // tandai_tidak_lengkap
            $izin->update([
                'status_dokumen'      => PengajuanIzin::DOKUMEN_TIDAK_LENGKAP,
                'catatan_dokumen'     => $request->catatan_dokumen,
                'diverifikasi_hr'     => $hr->id_pengguna,
                'waktu_verifikasi_hr' => now(),
            ]);

            // Notifikasi ke Admin Outsource perusahaan karyawan
            $this->notifikasiDokumenTidakLengkap($izin, $hr->id_pengguna);

            $pesan = "Dokumen pengajuan izin {$izin->karyawan->nama_lengkap} ditandai Tidak Lengkap. Admin Outsource telah dinotifikasi.";
        }

        // Audit log
        AuditLogService::catat(
            pengguna:    $hr,
            jenis:       AuditLog::JENIS_IZIN,
            idReferensi: $izin->id_izin,
            aksi:        AuditLog::AKSI_UPDATE,
            catatan:     "HR verifikasi dokumen ({$aksi}): {$izin->karyawan->nama_lengkap}" . ($request->catatan_dokumen ? " — {$request->catatan_dokumen}" : ''),
            sebelum:     $sebelum,
            sesudah:     $izin->fresh()->toArray(),
        );

        $izin->refresh()->load([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen,id_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen',
        ]);

        return response()->json([
            'status'  => true,
            'message' => $pesan,
            'data'    => $this->formatIzin($izin, detail: true),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  STREAM DOKUMEN — Preview file untuk HR
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/dokumen/{id}/stream/{docId}
     *
     * Stream file dokumen ke browser untuk preview.
     * HR dapat mengakses semua dokumen dari semua pengajuan izin.
     */
    public function streamDokumen(int $id, int $docId): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Validasi izin milik karyawan yang sudah diapprove
        $izin = PengajuanIzin::where('status', PengajuanIzin::STATUS_DISETUJUI)->find($id);

        if (! $izin) {
            return response()->json([
                'status'  => false,
                'message' => 'Pengajuan izin tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $dokumen = DokumenIzin::where('id_dokumen', $docId)
            ->where('id_izin', $izin->id_izin)
            ->first();

        if (! $dokumen || ! Storage::exists($dokumen->path_file)) {
            return response()->json([
                'status'  => false,
                'message' => 'File dokumen tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $mimeMap = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];

        $mime        = $mimeMap[strtolower($dokumen->tipe_file)] ?? 'application/octet-stream';
        $isInline    = in_array(strtolower($dokumen->tipe_file), ['pdf', 'jpg', 'jpeg', 'png']);
        $disposition = $isInline ? 'inline' : 'attachment';

        return Storage::response(
            $dokumen->path_file,
            $dokumen->nama_file,
            [
                'Content-Type'        => $mime,
                'Content-Disposition' => "{$disposition}; filename=\"{$dokumen->nama_file}\"",
            ]
        );
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Helpers
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Kirim notifikasi ke semua Admin Outsource perusahaan karyawan
     * bahwa dokumen izin ditandai tidak lengkap oleh HR.
     */
    private function notifikasiDokumenTidakLengkap(PengajuanIzin $izin, int $idPengirim): void
    {
        $adminList = $izin->karyawan->perusahaan
            ->adminProfiles()
            ->with('pengguna:id_pengguna')
            ->get()
            ->pluck('pengguna.id_pengguna');

        foreach ($adminList as $idAdmin) {
            NotifikasiService::kirim(
                idPenerima:  $idAdmin,
                judul:       "Dokumen izin {$izin->karyawan->nama_lengkap} belum lengkap",
                isi:         "HR menemukan kekurangan dokumen pada pengajuan izin {$izin->karyawan->nama_lengkap}"
                           . ($izin->catatan_dokumen ? ". Catatan: {$izin->catatan_dokumen}" : '.'),
                jenis:       Notifikasi::JENIS_IZIN,
                idPengirim:  $idPengirim,
                idReferensi: $izin->id_izin,
            );
        }
    }

    private function formatIzin(PengajuanIzin $i, bool $detail = false): array
    {
        $tanggalMulai   = $i->tanggal_izin;
        $tanggalSelesai = $i->tanggal_selesai_izin ?? $i->tanggal_izin;
        $jumlahHari     = (int) $tanggalMulai->diffInDays($tanggalSelesai) + 1;

        $base = [
            'id_izin'              => $i->id_izin,
            'tanggal_izin'         => $i->tanggal_izin?->format('Y-m-d'),
            'tanggal_selesai_izin' => $i->tanggal_selesai_izin?->format('Y-m-d'),
            'jumlah_hari'          => $jumlahHari,
            'karyawan'             => $i->karyawan ? [
                'id_karyawan'    => $i->karyawan->id_karyawan,
                'nama_lengkap'   => $i->karyawan->nama_lengkap,
                'nomor_karyawan' => $i->karyawan->nomor_karyawan,
                'departemen'     => $i->karyawan->departemen?->nama_departemen,
                'perusahaan'     => $i->karyawan->perusahaan?->nama_perusahaan,
            ] : null,
            'jenis_izin'           => $i->jenisIzin ? [
                'nama_jenis'    => $i->jenisIzin->nama_jenis,
                'wajib_dokumen' => $i->jenisIzin->wajib_dokumen,
            ] : null,
            'status'               => $i->status,
            'status_dokumen'       => $i->status_dokumen,
            'catatan_dokumen'      => $i->catatan_dokumen,
            'jumlah_dokumen'       => $i->dokumen?->count() ?? 0,
            'diajukan_pada'        => $i->diajukan_pada?->toDateTimeString(),
            'waktu_validasi_admin' => $i->waktu_validasi_admin?->toDateTimeString(),
            'waktu_verifikasi_hr'  => $i->waktu_verifikasi_hr?->toDateTimeString(),
        ];

        if ($detail) {
            $base['karyawan']['posisi']          = $i->karyawan?->posisi;
            $base['karyawan']['kode_departemen'] = $i->karyawan?->departemen?->kode_departemen;
            $base['keterangan']                  = $i->keterangan;
            $base['catatan_penolakan']           = $i->catatan_penolakan;
            $base['validator_admin']             = $i->validatorAdmin?->nama_lengkap;
            $base['jenis_izin']['keterangan']    = $i->jenisIzin?->keterangan;

            $base['dokumen'] = $i->dokumen?->map(fn($d) => [
                'id_dokumen'    => $d->id_dokumen,
                'nama_file'     => $d->nama_file,
                'tipe_file'     => $d->tipe_file,
                'ukuran_kb'     => $d->ukuran_kb,
                'diunggah_pada' => $d->diunggah_pada?->toDateTimeString(),
            ])->values()->all() ?? [];
        }

        return $base;
    }
}
