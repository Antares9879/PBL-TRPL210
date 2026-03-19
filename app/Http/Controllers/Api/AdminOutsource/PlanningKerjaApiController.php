<?php

namespace App\Http\Controllers\Api\AdminOutsource;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOutsource\StorePlanningRequest;
use App\Models\Karyawan;
use App\Models\PlanningKerja;
use App\Models\JadwalKerja;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PlanningKerjaApiController — F08, F09
 *
 * F08: Input planning kerja bulanan untuk karyawan yang dikelola Admin.
 * F09: Upload ulang (replace) planning yang sudah ada apabila ada kesalahan.
 *
 * Business rules:
 *   - Satu planning per perusahaan per bulan per tahun (per versi).
 *   - Upload ulang membuat versi baru dan menonaktifkan versi lama.
 *   - Duplikasi jadwal (karyawan+tanggal) dalam satu planning ditolak.
 *   - Karyawan di-scope ke perusahaan Admin yang login.
 *
 * Endpoints:
 *   GET    /api/admin/planning              → index()
 *   POST   /api/admin/planning              → store()       F08
 *   GET    /api/admin/planning/{id}         → show()
 *   POST   /api/admin/planning/{id}/upload-ulang → uploadUlang() F09
 */
class PlanningKerjaApiController extends Controller
{
    private function getIdPerusahaan(): int
    {
        return auth()->user()->adminOutsourceProfile->id_perusahaan;
    }

    // ── INDEX ─────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $idPerusahaan = $this->getIdPerusahaan();

        $query = PlanningKerja::where('id_perusahaan', $idPerusahaan)
            ->withCount('jadwal');

        if ($request->filled('bulan')) {
            $query->where('periode_bulan', $request->bulan);
        }

        if ($request->filled('tahun')) {
            $query->where('periode_tahun', $request->tahun);
        }

        $data = $query
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan')
            ->orderByDesc('versi')
            ->paginate(20);

        $data->getCollection()->transform(fn($p) => $this->formatPlanning($p));

        return response()->json([
            'status'  => true,
            'message' => 'Data planning berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    // ── SHOW ──────────────────────────────────────────────────────────────────

    public function show(int $planning): JsonResponse
    {
        $data = $this->findPlanning($planning);

        if (! $data) {
            return $this->notFound();
        }

        // Load jadwal detail termasuk shift dan karyawan
        $data->load([
            'jadwal.karyawan:id_karyawan,nama_lengkap,nomor_karyawan',
            'jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Detail planning berhasil dimuat.',
            'data'    => $this->formatPlanningDetail($data),
        ]);
    }

    // ── STORE (F08) ───────────────────────────────────────────────────────────

    /**
     * Buat planning kerja baru.
     *
     * Flow:
     * 1. Validasi tidak ada konflik jadwal per karyawan per tanggal.
     * 2. Buat header planning (status: aktif, versi: 1).
     * 3. Bulk-insert detail jadwal_kerja.
     * 4. Kirim notifikasi ke setiap karyawan yang terjadwal.
     * 5. Catat audit log.
     */
    public function store(StorePlanningRequest $request): JsonResponse
    {
        $idPerusahaan = $this->getIdPerusahaan();
        $admin        = auth()->user();

        // Cek apakah sudah ada planning aktif untuk periode ini
        $planningExisting = PlanningKerja::where('id_perusahaan', $idPerusahaan)
            ->where('periode_bulan', $request->periode_bulan)
            ->where('periode_tahun',  $request->periode_tahun)
            ->where('status', PlanningKerja::STATUS_AKTIF)
            ->first();

        if ($planningExisting) {
            return response()->json([
                'status'  => false,
                'message' => 'Planning aktif untuk periode ini sudah ada. Gunakan fitur Upload Ulang untuk menggantikannya.',
                'data'    => ['id_planning' => $planningExisting->id_planning],
            ], 422);
        }

        // Validasi semua karyawan milik perusahaan ini
        $idKaryawanList = collect($request->jadwal)->pluck('id_karyawan')->unique()->values();
        $karyawanValid  = Karyawan::where('id_perusahaan', $idPerusahaan)
            ->where('status', 'aktif')
            ->whereIn('id_karyawan', $idKaryawanList)
            ->pluck('id_karyawan');

        $karyawanTidakValid = $idKaryawanList->diff($karyawanValid);
        if ($karyawanTidakValid->isNotEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'Beberapa karyawan tidak valid atau bukan milik perusahaan Anda.',
                'data'    => ['id_karyawan_tidak_valid' => $karyawanTidakValid->values()],
            ], 422);
        }

        // Cek konflik jadwal (karyawan + tanggal harus unik dalam planning ini)
        $konflik = $this->deteksiKonflikJadwal($request->jadwal);
        if (! empty($konflik)) {
            return response()->json([
                'status'  => false,
                'message' => 'Terdapat konflik jadwal: satu karyawan dijadwalkan dua kali pada tanggal yang sama.',
                'data'    => ['konflik' => $konflik],
            ], 422);
        }

        try {
            $planning = DB::transaction(function () use ($request, $idPerusahaan, $admin) {

                // Hitung versi (selalu 1 untuk planning baru periode ini)
                $versiTerakhir = PlanningKerja::where('id_perusahaan', $idPerusahaan)
                    ->where('periode_bulan', $request->periode_bulan)
                    ->where('periode_tahun',  $request->periode_tahun)
                    ->max('versi') ?? 0;

                $planning = PlanningKerja::create([
                    'id_perusahaan' => $idPerusahaan,
                    'periode_bulan' => $request->periode_bulan,
                    'periode_tahun' => $request->periode_tahun,
                    'status'        => PlanningKerja::STATUS_AKTIF,
                    'versi'         => $versiTerakhir + 1,
                    'dibuat_oleh'   => $admin->id_pengguna,
                ]);

                // Bulk insert jadwal
                $jadwalData = collect($request->jadwal)->map(fn($j) => [
                    'id_planning'  => $planning->id_planning,
                    'id_karyawan'  => $j['id_karyawan'],
                    'id_shift'     => $j['id_shift'],
                    'tanggal_kerja'=> $j['tanggal_kerja'],
                    'is_hari_libur'=> $j['is_hari_libur'] ?? false,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ])->toArray();

                JadwalKerja::insert($jadwalData);

                return $planning;
            });

            // Kirim notifikasi ke karyawan yang terjadwal
            $idKaryawanPenggunaan = Karyawan::where('id_perusahaan', $idPerusahaan)
                ->whereIn('id_karyawan', $idKaryawanList)
                ->pluck('id_pengguna')
                ->toArray();

            NotifikasiService::planningBaru(
                idKaryawanList: $idKaryawanPenggunaan,
                periodeLabel:   $planning->periode_label,
                idPlanning:     $planning->id_planning,
                idPengirim:     $admin->id_pengguna,
            );

            // Audit log
            AuditLogService::catat(
                pengguna:    $admin,
                jenis:       AuditLog::JENIS_PLANNING,
                idReferensi: $planning->id_planning,
                aksi:        AuditLog::AKSI_CREATE,
                catatan:     "Planning kerja {$planning->periode_label} dibuat. Versi {$planning->versi}.",
            );

            return response()->json([
                'status'  => true,
                'message' => "Planning kerja {$planning->periode_label} berhasil dibuat.",
                'data'    => $this->formatPlanning($planning),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Gagal membuat planning', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Gagal membuat planning kerja. Silakan coba lagi.',
                'data'    => null,
            ], 500);
        }
    }

    // ── UPLOAD ULANG (F09) ────────────────────────────────────────────────────

    /**
     * Upload ulang planning: ganti jadwal lama dengan data baru.
     *
     * Flow:
     * 1. Validasi planning milik perusahaan Admin.
     * 2. Set status planning lama menjadi 'diperbarui'.
     * 3. Buat planning baru dengan versi+1 dan jadwal baru.
     * 4. Kirim notifikasi ke karyawan terdampak.
     * 5. Catat audit log.
     */
    public function uploadUlang(StorePlanningRequest $request, int $planning): JsonResponse
    {
        $planningLama = $this->findPlanning($planning);

        if (! $planningLama) {
            return $this->notFound();
        }

        $idPerusahaan = $this->getIdPerusahaan();
        $admin        = auth()->user();

        // Pastikan periode sama (tidak bisa ganti periode via upload ulang)
        if (
            $request->periode_bulan != $planningLama->periode_bulan ||
            $request->periode_tahun != $planningLama->periode_tahun
        ) {
            return response()->json([
                'status'  => false,
                'message' => 'Periode upload ulang harus sama dengan planning yang akan digantikan.',
                'data'    => null,
            ], 422);
        }

        // Validasi karyawan
        $idKaryawanList = collect($request->jadwal)->pluck('id_karyawan')->unique();
        $karyawanValid  = Karyawan::where('id_perusahaan', $idPerusahaan)
            ->whereIn('id_karyawan', $idKaryawanList)
            ->pluck('id_karyawan');

        $karyawanTidakValid = $idKaryawanList->diff($karyawanValid);
        if ($karyawanTidakValid->isNotEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'Beberapa karyawan tidak valid.',
                'data'    => ['id_karyawan_tidak_valid' => $karyawanTidakValid->values()],
            ], 422);
        }

        $konflik = $this->deteksiKonflikJadwal($request->jadwal);
        if (! empty($konflik)) {
            return response()->json([
                'status'  => false,
                'message' => 'Terdapat konflik jadwal.',
                'data'    => ['konflik' => $konflik],
            ], 422);
        }

        try {
            $planningBaru = DB::transaction(function () use ($request, $planningLama, $idPerusahaan, $admin) {

                // Set planning lama jadi diperbarui
                $planningLama->update(['status' => PlanningKerja::STATUS_DIPERBARUI]);

                // Buat planning baru
                $planningBaru = PlanningKerja::create([
                    'id_perusahaan' => $idPerusahaan,
                    'periode_bulan' => $planningLama->periode_bulan,
                    'periode_tahun' => $planningLama->periode_tahun,
                    'status'        => PlanningKerja::STATUS_AKTIF,
                    'versi'         => $planningLama->versi + 1,
                    'dibuat_oleh'   => $admin->id_pengguna,
                ]);

                $jadwalData = collect($request->jadwal)->map(fn($j) => [
                    'id_planning'   => $planningBaru->id_planning,
                    'id_karyawan'   => $j['id_karyawan'],
                    'id_shift'      => $j['id_shift'],
                    'tanggal_kerja' => $j['tanggal_kerja'],
                    'is_hari_libur' => $j['is_hari_libur'] ?? false,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ])->toArray();

                JadwalKerja::insert($jadwalData);

                return $planningBaru;
            });

            // Notifikasi
            $idPenggunaKaryawan = Karyawan::where('id_perusahaan', $idPerusahaan)
                ->whereIn('id_karyawan', $idKaryawanList)
                ->pluck('id_pengguna')
                ->toArray();

            NotifikasiService::planningBaru(
                idKaryawanList: $idPenggunaKaryawan,
                periodeLabel:   $planningBaru->periode_label . ' (diperbarui)',
                idPlanning:     $planningBaru->id_planning,
                idPengirim:     $admin->id_pengguna,
            );

            AuditLogService::catat(
                pengguna:    $admin,
                jenis:       AuditLog::JENIS_PLANNING,
                idReferensi: $planningBaru->id_planning,
                aksi:        AuditLog::AKSI_UPLOAD,
                catatan:     "Planning {$planningBaru->periode_label} diperbarui. Versi {$planningBaru->versi} menggantikan versi {$planningLama->versi}.",
            );

            return response()->json([
                'status'  => true,
                'message' => "Planning {$planningBaru->periode_label} berhasil diperbarui ke versi {$planningBaru->versi}.",
                'data'    => $this->formatPlanning($planningBaru),
            ]);

        } catch (\Throwable $e) {
            Log::error('Gagal upload ulang planning', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui planning. Silakan coba lagi.',
                'data'    => null,
            ], 500);
        }
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────

    private function findPlanning(int $id): ?PlanningKerja
    {
        return PlanningKerja::where('id_planning', $id)
            ->where('id_perusahaan', $this->getIdPerusahaan())
            ->first();
    }

    /**
     * Deteksi konflik: karyawan yang sama dijadwalkan dua kali pada hari yang sama.
     */
    private function deteksiKonflikJadwal(array $jadwal): array
    {
        $seen    = [];
        $konflik = [];

        foreach ($jadwal as $item) {
            $key = "{$item['id_karyawan']}|{$item['tanggal_kerja']}";
            if (isset($seen[$key])) {
                $konflik[] = [
                    'id_karyawan'  => $item['id_karyawan'],
                    'tanggal_kerja'=> $item['tanggal_kerja'],
                    'pesan'        => 'Karyawan ini sudah dijadwalkan pada tanggal tersebut.',
                ];
            }
            $seen[$key] = true;
        }

        return $konflik;
    }

    private function formatPlanning(PlanningKerja $p): array
    {
        return [
            'id_planning'    => $p->id_planning,
            'periode_label'  => $p->periode_label,
            'periode_bulan'  => $p->periode_bulan,
            'periode_tahun'  => $p->periode_tahun,
            'status'         => $p->status,
            'versi'          => $p->versi,
            'jumlah_jadwal'  => $p->jadwal_count ?? null,
            'dibuat_oleh'    => $p->dibuat_oleh,
            'created_at'     => $p->created_at->toDateTimeString(),
        ];
    }

    private function formatPlanningDetail(PlanningKerja $p): array
    {
        return array_merge($this->formatPlanning($p), [
            'jadwal' => $p->jadwal->map(fn($j) => [
                'id_jadwal'     => $j->id_jadwal,
                'tanggal_kerja' => $j->tanggal_kerja->format('Y-m-d'),
                'is_hari_libur' => $j->is_hari_libur,
                'karyawan'      => $j->karyawan ? [
                    'id_karyawan'   => $j->karyawan->id_karyawan,
                    'nama_lengkap'  => $j->karyawan->nama_lengkap,
                    'nomor_karyawan'=> $j->karyawan->nomor_karyawan,
                ] : null,
                'shift'         => $j->shift ? [
                    'id_shift'   => $j->shift->id_shift,
                    'nama_shift' => $j->shift->nama_shift,
                    'jam_masuk'  => substr($j->shift->jam_masuk, 0, 5),
                    'jam_pulang' => substr($j->shift->jam_pulang, 0, 5),
                ] : null,
            ])->values(),
        ]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => 'Planning tidak ditemukan.',
            'data'    => null,
        ], 404);
    }
}