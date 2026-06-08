<?php

namespace App\Http\Controllers\Api\AdminOutsource;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOutsource\ValidasiAbsensiRequest;
use App\Http\Requests\AdminOutsource\ValidasiIzinRequest;
use App\Models\Absensi;
use App\Models\JadwalKerja;
use App\Models\Pengguna;
use App\Models\PengajuanIzin;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Services\NotifikasiService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ValidasiAbsensiApiController extends Controller
{
    private function getIdPerusahaan(): int
    {
        return $this->authenticatedPengguna()->adminOutsourceProfile->id_perusahaan;
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
    //  F10 — VALIDASI KEHADIRAN (SINGLE)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Single Approve - dengan detail lengkap di response
     */
    public function approve(int $id): JsonResponse
    {
        $admin = $this->authenticatedPengguna();

        $absensi = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_pengguna,id_perusahaan',
            'jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang',
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

        $absensi->update([
            'status_validasi'   => Absensi::VALIDASI_DISETUJUI,
            'status_kehadiran'  => Absensi::STATUS_HADIR,
            'divalidasi_oleh'   => $admin->id_pengguna,
            'waktu_validasi'    => now(),
            'catatan_penolakan' => null,
        ]);

        AuditLogService::catat(
            pengguna:    $admin,
            jenis:       AuditLog::JENIS_ABSENSI,
            idReferensi: $absensi->id_absensi,
            aksi:        AuditLog::AKSI_APPROVE,
            catatan:     null,
            sebelum:     $sebelum,
            sesudah:     $absensi->fresh()->toArray(),
        );

        NotifikasiService::absensiDivalidasi(
            idKaryawan: $absensi->karyawan->id_pengguna,
            statusBaru: 'disetujui',
            tanggal:    $absensi->tanggal_absensi->format('d M Y'),
            catatan:    null,
            idAbsensi:  $absensi->id_absensi,
            idPengirim: $admin->id_pengguna,
        );

        $absensi->refresh();

        return response()->json([
            'status'  => true,
            'message' => 'Absensi berhasil disetujui.',
            'data'    => $this->formatAbsensiDetailed($absensi),
        ]);
    }

    /**
     * Single Reject - dengan form alasan
     */
    public function reject(ValidasiAbsensiRequest $request, int $id): JsonResponse
    {
        $admin = $this->authenticatedPengguna();

        $absensi = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_pengguna,id_perusahaan',
            'jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang',
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

        $absensi->update([
            'status_validasi'   => Absensi::VALIDASI_DITOLAK,
            'status_kehadiran'  => Absensi::STATUS_ALPA,
            'divalidasi_oleh'   => $admin->id_pengguna,
            'waktu_validasi'    => now(),
            'catatan_penolakan' => $request->catatan_penolakan,
        ]);

        AuditLogService::catat(
            pengguna:    $admin,
            jenis:       AuditLog::JENIS_ABSENSI,
            idReferensi: $absensi->id_absensi,
            aksi:        AuditLog::AKSI_REJECT,
            catatan:     $request->catatan_penolakan,
            sebelum:     $sebelum,
            sesudah:     $absensi->fresh()->toArray(),
        );

        NotifikasiService::absensiDivalidasi(
            idKaryawan: $absensi->karyawan->id_pengguna,
            statusBaru: 'ditolak',
            tanggal:    $absensi->tanggal_absensi->format('d M Y'),
            catatan:    $request->catatan_penolakan,
            idAbsensi:  $absensi->id_absensi,
            idPengirim: $admin->id_pengguna,
        );

        $absensi->refresh();

        return response()->json([
            'status'  => true,
            'message' => 'Absensi berhasil ditolak.',
            'data'    => $this->formatAbsensiDetailed($absensi),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  F10 — VALIDASI KEHADIRAN (BULK)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Bulk Approve - approve multiple absensi sekaligus
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        // Debug log
        \Log::info('Bulk Approve Called', [
            'request_all' => $request->all(),
            'request_input' => $request->input(),
            'content_type' => $request->header('Content-Type'),
            'method' => $request->method(),
        ]);
        
        $admin = $this->authenticatedPengguna();
        $idPerusahaan = $this->getIdPerusahaan();
        
        // Ambil IDs langsung tanpa validasi exists dulu
        $ids = $request->input('absensi_ids', []);
        
        \Log::info('IDs received', ['ids' => $ids, 'type' => gettype($ids)]);
        
        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'Pilih minimal satu absensi untuk divalidasi.',
                'data' => ['ids' => $ids, 'is_array' => is_array($ids)],
            ], 422);
        }

        $absensiList = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,id_pengguna,id_perusahaan',
        ])
        ->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $idPerusahaan))
        ->whereIn('id_absensi', $ids)
        ->where('status_validasi', Absensi::VALIDASI_MENUNGGU)
        ->get();

        if ($absensiList->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'Tidak ada absensi yang dapat diproses. Pastikan absensi masih berstatus menunggu validasi.',
                'data'    => null,
            ], 422);
        }

        $successCount = 0;
        $failed = [];

        foreach ($absensiList as $absensi) {
            try {
                $sebelum = $absensi->toArray();

                $absensi->update([
                    'status_validasi'   => Absensi::VALIDASI_DISETUJUI,
                    'status_kehadiran'  => Absensi::STATUS_HADIR,
                    'divalidasi_oleh'   => $admin->id_pengguna,
                    'waktu_validasi'    => now(),
                    'catatan_penolakan' => null,
                ]);

                AuditLogService::catat(
                    pengguna:    $admin,
                    jenis:       AuditLog::JENIS_ABSENSI,
                    idReferensi: $absensi->id_absensi,
                    aksi:        AuditLog::AKSI_APPROVE,
                    catatan:     'Bulk approve',
                    sebelum:     $sebelum,
                    sesudah:     $absensi->fresh()->toArray(),
                );

                NotifikasiService::absensiDivalidasi(
                    idKaryawan: $absensi->karyawan->id_pengguna,
                    statusBaru: 'disetujui',
                    tanggal:    $absensi->tanggal_absensi->format('d M Y'),
                    catatan:    null,
                    idAbsensi:  $absensi->id_absensi,
                    idPengirim: $admin->id_pengguna,
                );

                $successCount++;

            } catch (\Exception $e) {
                $failed[] = [
                    'id' => $absensi->id_absensi,
                    'nama' => $absensi->karyawan->nama_lengkap ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status'  => true,
            'message' => "{$successCount} absensi berhasil disetujui.",
            'data'    => [
                'success_count' => $successCount,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Bulk Reject - reject multiple dengan 2 mode
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $admin = $this->authenticatedPengguna();
        
        // Validasi manual
        $validated = $request->validate([
            'mode' => ['required', 'in:same_reason,individual_reason'],
            'absensi_ids' => ['required_if:mode,same_reason', 'array'],
            'absensi_ids.*' => ['integer', 'exists:absensi,id_absensi'],
            'alasan_penolakan' => ['required_if:mode,same_reason', 'string', 'max:200'],
            'keterangan_tambahan' => ['nullable', 'string', 'max:500'],
            'rejections' => ['required_if:mode,individual_reason', 'array'],
            'rejections.*.id' => ['integer', 'exists:absensi,id_absensi'],
            'rejections.*.alasan_penolakan' => ['string', 'max:200'],
            'rejections.*.keterangan_tambahan' => ['nullable', 'string', 'max:500'],
        ]);
        
        $mode = $validated['mode'];

        if ($mode === 'same_reason') {
            return $this->bulkRejectSameReason($request, $admin);
        } else {
            return $this->bulkRejectIndividualReason($request, $admin);
        }
    }

    /**
     * Bulk Reject - mode alasan sama untuk semua
     */
    private function bulkRejectSameReason(Request $request, Pengguna $admin): JsonResponse
    {
        $ids = $request->input('absensi_ids');
        $alasan = $request->input('alasan_penolakan');
        $keterangan = $request->input('keterangan_tambahan', '');
        
        $catatanFinal = $keterangan ? "{$alasan} — {$keterangan}" : $alasan;

        $absensiList = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,id_pengguna,id_perusahaan',
        ])
        ->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $this->getIdPerusahaan()))
        ->whereIn('id_absensi', $ids)
        ->where('status_validasi', Absensi::VALIDASI_MENUNGGU)
        ->get();

        if ($absensiList->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'Tidak ada absensi yang dapat diproses.',
                'data'    => null,
            ], 422);
        }

        $successCount = 0;
        $failed = [];

        foreach ($absensiList as $absensi) {
            try {
                $sebelum = $absensi->toArray();

                $absensi->update([
                    'status_validasi'   => Absensi::VALIDASI_DITOLAK,
                    'status_kehadiran'  => Absensi::STATUS_ALPA,
                    'divalidasi_oleh'   => $admin->id_pengguna,
                    'waktu_validasi'    => now(),
                    'catatan_penolakan' => $catatanFinal,
                ]);

                AuditLogService::catat(
                    pengguna:    $admin,
                    jenis:       AuditLog::JENIS_ABSENSI,
                    idReferensi: $absensi->id_absensi,
                    aksi:        AuditLog::AKSI_REJECT,
                    catatan:     $catatanFinal,
                    sebelum:     $sebelum,
                    sesudah:     $absensi->fresh()->toArray(),
                );

                NotifikasiService::absensiDivalidasi(
                    idKaryawan: $absensi->karyawan->id_pengguna,
                    statusBaru: 'ditolak',
                    tanggal:    $absensi->tanggal_absensi->format('d M Y'),
                    catatan:    $catatanFinal,
                    idAbsensi:  $absensi->id_absensi,
                    idPengirim: $admin->id_pengguna,
                );

                $successCount++;

            } catch (\Exception $e) {
                $failed[] = [
                    'id' => $absensi->id_absensi,
                    'nama' => $absensi->karyawan->nama_lengkap ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status'  => true,
            'message' => "{$successCount} absensi berhasil ditolak.",
            'data'    => [
                'success_count' => $successCount,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Bulk Reject - mode alasan per-item
     */
    private function bulkRejectIndividualReason(Request $request, Pengguna $admin): JsonResponse
    {
        $rejections = $request->input('rejections');
        $ids = array_column($rejections, 'id');

        $absensiList = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,id_pengguna,id_perusahaan',
        ])
        ->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $this->getIdPerusahaan()))
        ->whereIn('id_absensi', $ids)
        ->where('status_validasi', Absensi::VALIDASI_MENUNGGU)
        ->get()
        ->keyBy('id_absensi');

        if ($absensiList->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'Tidak ada absensi yang dapat diproses.',
                'data'    => null,
            ], 422);
        }

        $successCount = 0;
        $failed = [];

        foreach ($rejections as $rejection) {
            $absensi = $absensiList->get($rejection['id']);
            
            if (!$absensi) {
                $failed[] = [
                    'id' => $rejection['id'],
                    'error' => 'Absensi tidak ditemukan atau sudah diproses',
                ];
                continue;
            }

            try {
                $alasan = $rejection['alasan_penolakan'];
                $keterangan = $rejection['keterangan_tambahan'] ?? '';
                $catatanFinal = $keterangan ? "{$alasan} — {$keterangan}" : $alasan;

                $sebelum = $absensi->toArray();

                $absensi->update([
                    'status_validasi'   => Absensi::VALIDASI_DITOLAK,
                    'status_kehadiran'  => Absensi::STATUS_ALPA,
                    'divalidasi_oleh'   => $admin->id_pengguna,
                    'waktu_validasi'    => now(),
                    'catatan_penolakan' => $catatanFinal,
                ]);

                AuditLogService::catat(
                    pengguna:    $admin,
                    jenis:       AuditLog::JENIS_ABSENSI,
                    idReferensi: $absensi->id_absensi,
                    aksi:        AuditLog::AKSI_REJECT,
                    catatan:     $catatanFinal,
                    sebelum:     $sebelum,
                    sesudah:     $absensi->fresh()->toArray(),
                );

                NotifikasiService::absensiDivalidasi(
                    idKaryawan: $absensi->karyawan->id_pengguna,
                    statusBaru: 'ditolak',
                    tanggal:    $absensi->tanggal_absensi->format('d M Y'),
                    catatan:    $catatanFinal,
                    idAbsensi:  $absensi->id_absensi,
                    idPengirim: $admin->id_pengguna,
                );

                $successCount++;

            } catch (\Exception $e) {
                $failed[] = [
                    'id' => $absensi->id_absensi,
                    'nama' => $absensi->karyawan->nama_lengkap ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status'  => true,
            'message' => "{$successCount} absensi berhasil ditolak.",
            'data'    => [
                'success_count' => $successCount,
                'failed' => $failed,
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  F10 — VALIDASI KEHADIRAN (LEGACY - untuk backward compatibility)
    // ════════════════════════════════════════════════════════════════════════

    public function validasi(ValidasiAbsensiRequest $request, int $id): JsonResponse
    {
        $admin = $this->authenticatedPengguna();

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

        $status = $request->has('status')
            ? trim((string) $request->query('status', ''))
            : null;

        if ($request->has('status')) {
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

        // Untuk alur validasi Admin:
        // - izin non-wajib dokumen tetap tampil
        // - izin wajib dokumen hanya tampil jika dokumen sudah diunggah
        //   (status_dokumen = sudah_upload + minimal ada file)
        $pendingDokumenSiap = function ($q) {
            $q->whereHas('jenisIzin', fn($jenis) => $jenis->where('wajib_dokumen', false))
                ->orWhere(function ($wajib) {
                    $wajib->whereHas('jenisIzin', fn($jenis) => $jenis->where('wajib_dokumen', true))
                        ->where('status_dokumen', PengajuanIzin::DOKUMEN_SUDAH_UPLOAD)
                        ->whereHas('dokumen');
                });
        };

        if ($status === PengajuanIzin::STATUS_MENUNGGU || $status === null) {
            $query->where($pendingDokumenSiap);
        } elseif ($status === '') {
            // Saat "semua status", tetap sembunyikan izin menunggu yang belum layak divalidasi.
            $query->where(function ($q) use ($pendingDokumenSiap) {
                $q->where('status', '!=', PengajuanIzin::STATUS_MENUNGGU)
                    ->orWhere(function ($pending) use ($pendingDokumenSiap) {
                        $pending->where('status', PengajuanIzin::STATUS_MENUNGGU)
                            ->where($pendingDokumenSiap);
                    });
            });
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
        $admin = $this->authenticatedPengguna();

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

        if ($request->aksi === 'approve' && $izin->jenisIzin->wajib_dokumen) {
            if ($izin->status_dokumen !== PengajuanIzin::DOKUMEN_SUDAH_UPLOAD) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tidak dapat menyetujui izin ini. Dokumen wajib harus berstatus sudah upload.',
                    'data'    => [
                        'status_dokumen' => $izin->status_dokumen,
                    ],
                ], 422);
            }

            if ($izin->dokumen->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tidak dapat menyetujui izin ini. Dokumen pendukung wajib diunggah terlebih dahulu.',
                    'data'    => null,
                ], 422);
            }
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

    private function createOrUpdateAbsensiForPermission(PengajuanIzin $izin, Pengguna $admin): void
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
        Pengguna $admin,
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

    private function formatAbsensiDetailed(Absensi $a): array
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
            'lokasi_check_in'     => $a->latitude_check_in && $a->longitude_check_in 
                ? "{$a->latitude_check_in}, {$a->longitude_check_in}" 
                : null,
            'lokasi_check_out'    => $a->latitude_check_out && $a->longitude_check_out 
                ? "{$a->latitude_check_out}, {$a->longitude_check_out}" 
                : null,
            'is_lokasi_valid_in'  => $a->is_lokasi_valid_in,
            'is_lokasi_valid_out' => $a->is_lokasi_valid_out,
            'jarak_check_in'      => $a->jarak_check_in ? round($a->jarak_check_in) . ' meter' : null,
            'jarak_check_out'     => $a->jarak_check_out ? round($a->jarak_check_out) . ' meter' : null,
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

    private function authenticatedPengguna(): Pengguna
    {
        $user = Auth::user();

        if (! $user instanceof Pengguna) {
            throw new AuthenticationException('Pengguna tidak terautentikasi.');
        }

        return $user;
    }
}
