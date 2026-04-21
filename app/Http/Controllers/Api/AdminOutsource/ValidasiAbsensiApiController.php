<?php

namespace App\Http\Controllers\Api\AdminOutsource;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOutsource\ValidasiAbsensiRequest;
use App\Http\Requests\AdminOutsource\ValidasiIzinRequest;
use App\Models\Absensi;
use App\Models\JadwalKerja;
use App\Models\PengajuanIzin;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValidasiAbsensiApiController extends Controller
{
    private function getIdPerusahaan(): int
    {
        return auth()->user()->adminOutsourceProfile->id_perusahaan;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  F11 — PANTAU STATUS ABSENSI
    // ════════════════════════════════════════════════════════════════════════

    public function index(Request $request): JsonResponse
    {
        $idPerusahaan = $this->getIdPerusahaan();

        $query = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_perusahaan',
            'jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang',
        ])
        ->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $idPerusahaan));

        if ($request->filled('status_validasi')) {
            $query->where('status_validasi', $request->status_validasi);
        }

        if ($request->filled('status_kehadiran')) {
            $query->where('status_kehadiran', $request->status_kehadiran);
        }

        // ── FIX 3: handle ?tanggal= dari validasi-absensi.js ─────────────────
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_absensi', $request->tanggal);
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal_absensi', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_absensi', '<=', $request->tanggal_sampai);
        }

        if ($request->boolean('hari_ini')) {
            $query->whereDate('tanggal_absensi', today());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', fn($q) => $q
                ->where('nama_lengkap', 'like', "%{$search}%")
                ->orWhere('nomor_karyawan', 'like', "%{$search}%")
            );
        }

        $data = $query
            ->orderByDesc('tanggal_absensi')
            ->orderBy('status_validasi')
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
                'status_validasi'   => Absensi::VALIDASI_DISETUJUI,
                'status_kehadiran'  => Absensi::STATUS_HADIR,
                'divalidasi_oleh'   => $admin->id_pengguna,
                'waktu_validasi'    => now(),
                'catatan_penolakan' => null,
            ]);
        } else {
            $absensi->update([
                'status_validasi'   => Absensi::VALIDASI_DITOLAK,
                'status_kehadiran'  => Absensi::STATUS_ALPA,
                'divalidasi_oleh'   => $admin->id_pengguna,
                'waktu_validasi'    => now(),
                'catatan_penolakan' => $request->catatan_penolakan,
            ]);
        }

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

        NotifikasiService::absensiDivalidasi(
            idKaryawan: $absensi->karyawan->id_pengguna,
            statusBaru: $aksi === 'approve' ? 'disetujui' : 'ditolak',
            tanggal:    $absensi->tanggal_absensi->format('d M Y'),
            catatan:    $request->catatan_penolakan,
            idAbsensi:  $absensi->id_absensi,
            idPengirim: $admin->id_pengguna,
        );

        $absensi->refresh();

        return response()->json([
            'status'  => true,
            'message' => $aksi === 'approve' ? 'Absensi berhasil disetujui.' : 'Absensi berhasil ditolak.',
            'data'    => $this->formatAbsensi($absensi),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  F10 — VALIDASI IZIN
    // ════════════════════════════════════════════════════════════════════════

    public function indexIzin(Request $request): JsonResponse
    {
        $idPerusahaan = $this->getIdPerusahaan();

        $query = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen:id_dokumen,id_izin,nama_file,tipe_file,ukuran_kb,diunggah_pada',
        ])
        ->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $idPerusahaan));

        if ($request->has('status')) {
            $status      = trim((string) $request->query('status', ''));
            $statusValid = [
                PengajuanIzin::STATUS_MENUNGGU,
                PengajuanIzin::STATUS_DISETUJUI,
                PengajuanIzin::STATUS_DITOLAK,
            ];
            if ($status !== '' && in_array($status, $statusValid, true)) {
                $query->where('status', $status);
            }
        } else {
            $query->where('status', PengajuanIzin::STATUS_MENUNGGU);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', fn($q) => $q->where('nama_lengkap', 'like', "%{$search}%"));
        }

        $data = $query->orderByDesc('diajukan_pada')->paginate(20);
        $data->getCollection()->transform(fn($i) => $this->formatIzin($i));

        return response()->json([
            'status'  => true,
            'message' => 'Data pengajuan izin berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    public function showIzin(int $id): JsonResponse
    {
        $izin = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen:id_dokumen,id_izin,nama_file,tipe_file,ukuran_kb,diunggah_pada',
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

        return response()->json([
            'status'  => true,
            'message' => 'Detail pengajuan izin berhasil dimuat.',
            'data'    => $this->formatIzin($izin, includeDokumen: true),
        ]);
    }

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

            $this->createOrUpdateAbsensiForPermission($izin, $admin);

        } else {
            $izin->update([
                'status'               => PengajuanIzin::STATUS_DITOLAK,
                'divalidasi_admin'     => $admin->id_pengguna,
                'waktu_validasi_admin' => now(),
                'catatan_penolakan'    => $request->catatan_penolakan,
            ]);

            $this->markAsAlpaForRejectedPermission($izin, $admin, $request->catatan_penolakan);
        }

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

        NotifikasiService::izinDiproses(
            idKaryawan: $izin->karyawan->id_pengguna,
            statusBaru: $aksi === 'approve' ? 'disetujui' : 'ditolak',
            catatan:    $request->catatan_penolakan,
            idIzin:     $izin->id_izin,
            idPengirim: $admin->id_pengguna,
        );

        return response()->json([
            'status'  => true,
            'message' => $aksi === 'approve'
                ? 'Pengajuan izin berhasil disetujui.'
                : 'Pengajuan izin berhasil ditolak.',
            'data'    => $this->formatIzin($izin->fresh()->load(['karyawan', 'jenisIzin', 'dokumen'])),
        ]);
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────

    private function createOrUpdateAbsensiForPermission(PengajuanIzin $izin, $admin): void
    {
        $tanggalMulai   = $izin->tanggal_izin;
        $tanggalSelesai = $izin->getTanggalSelesaiEfektif();
        $jumlahHari     = (int) $tanggalMulai->diffInDays($tanggalSelesai) + 1;

        for ($i = 0; $i < $jumlahHari; $i++) {
            $tanggal = $tanggalMulai->copy()->addDays($i);

            $jadwal = JadwalKerja::where('id_karyawan', $izin->id_karyawan)
                ->whereDate('tanggal_kerja', $tanggal)
                ->where('is_hari_libur', false)
                ->first();

            if (! $jadwal) {
                continue;
            }

            $absensi = Absensi::where('id_karyawan', $izin->id_karyawan)
                ->whereDate('tanggal_absensi', $tanggal)
                ->first();

            if ($absensi) {
                $absensi->update([
                    'status_kehadiran' => Absensi::STATUS_IZIN,
                    'status_validasi'  => Absensi::VALIDASI_DISETUJUI,
                    'divalidasi_oleh'  => $admin->id_pengguna,
                    'waktu_validasi'   => now(),
                ]);
            } else {
                Absensi::create([
                    'id_karyawan'        => $izin->id_karyawan,
                    'id_jadwal'          => $jadwal->id_jadwal,
                    'tanggal_absensi'    => $tanggal,
                    'status_kehadiran'   => Absensi::STATUS_IZIN,
                    'status_validasi'    => Absensi::VALIDASI_DISETUJUI,
                    'divalidasi_oleh'    => $admin->id_pengguna,
                    'waktu_validasi'     => now(),
                    'menit_kerja_normal' => 0,
                    'menit_telat'        => 0,
                    'menit_pulang_cepat' => 0,
                    'menit_kelebihan'    => 0,
                ]);
            }
        }
    }

    private function markAsAlpaForRejectedPermission(
        PengajuanIzin $izin,
        $admin,
        ?string $catatanPenolakan
    ): void {
        $tanggalMulai   = $izin->tanggal_izin;
        $tanggalSelesai = $izin->getTanggalSelesaiEfektif();
        $jumlahHari     = (int) $tanggalMulai->diffInDays($tanggalSelesai) + 1;

        for ($i = 0; $i < $jumlahHari; $i++) {
            $tanggal = $tanggalMulai->copy()->addDays($i);

            // Cek jadwal kerja — skip jika hari libur atau tidak ada jadwal
            $jadwal = JadwalKerja::where('id_karyawan', $izin->id_karyawan)
                ->whereDate('tanggal_kerja', $tanggal)
                ->where('is_hari_libur', false)
                ->first();

            if (! $jadwal) {
                continue;
            }

            // Cek apakah tanggal ini masih dilindungi oleh pengajuan izin LAIN
            // yang sudah disetujui (bukan izin yang sedang ditolak ini).
            // Jika ya, jangan ubah apapun dan biarkan status izin tetap berlaku.
            $adaIzinLainDisetujui = PengajuanIzin::where('id_karyawan', $izin->id_karyawan)
                ->where('id_izin', '!=', $izin->id_izin)          // bukan izin ini
                ->where('status', PengajuanIzin::STATUS_DISETUJUI) // sudah disetujui
                ->where('tanggal_izin', '<=', $tanggal)
                ->where(function ($q) use ($tanggal) {
                    // cover single-day (tanggal_selesai_izin null) dan multi-day
                    $q->whereDate('tanggal_selesai_izin', '>=', $tanggal)
                    ->orWhere(function ($inner) use ($tanggal) {
                        $inner->whereNull('tanggal_selesai_izin')
                                ->whereDate('tanggal_izin', $tanggal);
                    });
                })
                ->exists();

            if ($adaIzinLainDisetujui) {
                // Tanggal ini masih dicover izin lain yang disetujui → skip
                continue;
            }
            // ─────────────────────────────────────────────────────────────────

            $absensi = Absensi::where('id_karyawan', $izin->id_karyawan)
                ->whereDate('tanggal_absensi', $tanggal)
                ->first();

            if ($absensi) {
                // Ada record absensi — skip apapun kondisinya.
                // Jika karyawan hadir (waktu_check_in != null) → jelas jangan diubah.
                // Jika record dibuat dari approve izin (waktu_check_in = null) →
                // juga jangan diubah, karena guard di atas sudah memastikan
                // tidak ada izin lain yang disetujui, artinya ini edge case
                // yang tidak perlu ditangani otomatis.
                continue;
            }

            // Hanya buat record alpa jika memang belum ada absensi sama sekali
            Absensi::create([
                    'id_karyawan'        => $izin->id_karyawan,
                    'id_jadwal'          => $jadwal->id_jadwal,
                    'tanggal_absensi'    => $tanggal,
                    'status_kehadiran'   => Absensi::STATUS_ALPA,
                    'status_validasi'    => Absensi::VALIDASI_DITOLAK,
                    'divalidasi_oleh'    => $admin->id_pengguna,
                    'waktu_validasi'     => now(),
                    'catatan_penolakan'  => $catatanPenolakan,
                    'menit_kerja_normal' => 0,
                    'menit_telat'        => 0,
                    'menit_pulang_cepat' => 0,
                    'menit_kelebihan'    => 0,
                ]);
        }
    }

    private function formatAbsensi(Absensi $a): array
    {
        return [
            'id_absensi'          => $a->id_absensi,
            'tanggal_absensi'     => $a->tanggal_absensi?->format('Y-m-d'),
            'karyawan'            => $a->karyawan ? [
                'id_karyawan'    => $a->karyawan->id_karyawan,
                'nama_lengkap'   => $a->karyawan->nama_lengkap,
                'nomor_karyawan' => $a->karyawan->nomor_karyawan,
            ] : null,
            'shift'               => $a->jadwal?->shift ? [
                'nama_shift' => $a->jadwal->shift->nama_shift,
                'jam_masuk'  => substr($a->jadwal->shift->jam_masuk, 0, 5),
                'jam_pulang' => substr($a->jadwal->shift->jam_pulang, 0, 5),
            ] : null,
            'waktu_check_in'      => $a->waktu_check_in?->format('H:i'),
            'waktu_check_out'     => $a->waktu_check_out?->format('H:i'),
            'is_lokasi_valid_in'  => $a->is_lokasi_valid_in,
            'is_lokasi_valid_out' => $a->is_lokasi_valid_out,
            'menit_kerja_normal'  => $a->menit_kerja_normal,
            'menit_telat'         => $a->menit_telat,
            'menit_pulang_cepat'  => $a->menit_pulang_cepat,
            'menit_kelebihan'     => $a->menit_kelebihan,
            'status_kehadiran'    => $a->status_kehadiran,
            'status_validasi'     => $a->status_validasi,
            'catatan_penolakan'   => $a->catatan_penolakan,
            'waktu_validasi'      => $a->waktu_validasi?->toDateTimeString(),
        ];
    }

    private function formatIzin(PengajuanIzin $i, bool $includeDokumen = false): array
    {
        $data = [
            'id_izin'              => $i->id_izin,
            'tanggal_izin'         => $i->tanggal_izin?->format('Y-m-d'),
            'karyawan'             => $i->karyawan ? [
                'id_karyawan'    => $i->karyawan->id_karyawan,
                'nama_lengkap'   => $i->karyawan->nama_lengkap,
                'nomor_karyawan' => $i->karyawan->nomor_karyawan ?? null,
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

        if ($includeDokumen) {
            $data['dokumen'] = $i->dokumen?->map(fn($d) => [
                'id_dokumen'    => $d->id_dokumen,
                'id_izin'       => $d->id_izin,
                'nama_file'     => $d->nama_file,
                'tipe_file'     => $d->tipe_file,
                'ukuran_kb'     => $d->ukuran_kb,
                'diunggah_pada' => $d->diunggah_pada?->toDateTimeString(),
            ])->values()->all() ?? [];
        }

        return $data;
    }
}