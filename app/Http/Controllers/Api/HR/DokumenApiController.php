<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\BulkVerifikasiDokumenRequest;
use App\Http\Requests\HR\VerifikasiDokumenRequest;
use App\Models\AuditLog;
use App\Models\DokumenIzin;
use App\Models\Notifikasi;
use App\Models\PengajuanIzin;
use App\Models\Pengguna;
use App\Services\AuditLogService;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen,id_perusahaan',
            'karyawan.departemen:id_departemen,nama_departemen',
            'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen:id_dokumen,id_izin,nama_file,tipe_file,ukuran_kb,diunggah_pada',
        ])
            ->where('status', PengajuanIzin::STATUS_DISETUJUI);

        $this->applyDokumenVerifikasiScope($query);

        if ($request->filled('status_dokumen')) {
            $query->where('status_dokumen', $request->status_dokumen);
        }

        if ($request->filled('jenis_izin')) {
            $jenisIzin = $request->jenis_izin;
            $query->whereHas('jenisIzin', function ($q) use ($jenisIzin) {
                if (is_numeric($jenisIzin)) {
                    $q->where('id_jenis_izin', (int) $jenisIzin);
                    return;
                }

                $normalized = str_replace('_', ' ', strtolower((string) $jenisIzin));
                $q->whereRaw('LOWER(nama_jenis) = ?', [$normalized]);
            });
        }

        if ($request->filled('id_departemen')) {
            $query->whereHas('karyawan', fn($q) => $q->where('id_departemen', $request->id_departemen));
        }

        if ($request->filled('id_perusahaan')) {
            $query->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $request->id_perusahaan));
        }

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal_izin', $request->bulan);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_izin', $request->tahun);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', fn($q) => $q->where('nama_lengkap', 'like', "%{$search}%"));
        }

        $query->orderByRaw("FIELD(status_dokumen, 'sudah_upload', 'tidak_lengkap', 'belum_upload', 'lengkap')")
            ->orderByDesc('diajukan_pada');

        $data = $query->paginate(20);
        $data->getCollection()->transform(fn($i) => $this->formatIzin($i));

        return response()->json([
            'status' => true,
            'message' => 'Daftar pengajuan izin berhasil dimuat.',
            'data' => $data,
        ]);
    }

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
        ->where('status', PengajuanIzin::STATUS_DISETUJUI);

        $this->applyDokumenVerifikasiScope($izin);

        $izin = $izin
        ->find($id);

        if (! $izin) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan izin tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail pengajuan izin berhasil dimuat.',
            'data' => $this->formatIzin($izin, detail: true),
        ]);
    }

    public function verifikasi(VerifikasiDokumenRequest $request, int $id): JsonResponse
    {
        $hr = Auth::user();
        if (! $hr instanceof Pengguna) {
            return response()->json([
                'status' => false,
                'message' => 'Pengguna tidak terautentikasi.',
                'data' => null,
            ], 401);
        }

        $izin = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,id_pengguna,id_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen',
        ])
        ->where('status', PengajuanIzin::STATUS_DISETUJUI);

        $this->applyDokumenVerifikasiScope($izin);

        $izin = $izin
        ->find($id);

        if (! $izin) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan izin tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        $aksi = $request->aksi;
        $sebelum = $izin->toArray();

        if ($aksi === 'tandai_lengkap') {
            if ($izin->jenisIzin->wajib_dokumen && $izin->dokumen->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak dapat menandai lengkap. Jenis izin ini wajib memiliki dokumen pendukung yang diunggah.',
                    'data' => null,
                ], 422);
            }

            $izin->update([
                'status_dokumen' => PengajuanIzin::DOKUMEN_LENGKAP,
                'catatan_dokumen' => null,
                'diverifikasi_hr' => $hr->id_pengguna,
                'waktu_verifikasi_hr' => now(),
            ]);

            $pesan = "Dokumen pengajuan izin {$izin->karyawan->nama_lengkap} berhasil ditandai Lengkap.";
        } else {
            $izin->update([
                'status_dokumen' => PengajuanIzin::DOKUMEN_TIDAK_LENGKAP,
                'catatan_dokumen' => $request->catatan_dokumen,
                'diverifikasi_hr' => $hr->id_pengguna,
                'waktu_verifikasi_hr' => now(),
            ]);

            $this->notifikasiDokumenTidakLengkap($izin, $hr->id_pengguna);
            $pesan = "Dokumen pengajuan izin {$izin->karyawan->nama_lengkap} ditandai Tidak Lengkap. Karyawan terkait telah dinotifikasi.";
        }

        AuditLogService::catat(
            pengguna: $hr,
            jenis: AuditLog::JENIS_IZIN,
            idReferensi: $izin->id_izin,
            aksi: AuditLog::AKSI_UPDATE,
            catatan: "HR verifikasi dokumen ({$aksi}): {$izin->karyawan->nama_lengkap}" . ($request->catatan_dokumen ? " - {$request->catatan_dokumen}" : ''),
            sebelum: $sebelum,
            sesudah: $izin->fresh()->toArray(),
        );

        $izin->refresh()->load([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen,id_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen',
        ]);

        return response()->json([
            'status' => true,
            'message' => $pesan,
            'data' => $this->formatIzin($izin, detail: true),
        ]);
    }

    public function bulkVerifikasi(BulkVerifikasiDokumenRequest $request): JsonResponse
    {
        $hr = Auth::user();
        if (! $hr instanceof Pengguna) {
            return response()->json([
                'status' => false,
                'message' => 'Pengguna tidak terautentikasi.',
                'data' => null,
            ], 401);
        }

        $ids = collect($request->input('ids', []))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $aksi = $request->input('aksi');
        $catatan = $request->input('catatan_dokumen');

        $izinMap = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,id_pengguna,id_perusahaan',
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen',
        ])
        ->where('status', PengajuanIzin::STATUS_DISETUJUI);

        $this->applyDokumenVerifikasiScope($izinMap);

        $izinMap = $izinMap
        ->whereIn('id_izin', $ids)
        ->get()
        ->keyBy('id_izin');

        $totalSuccess = 0;
        $failedIds = [];
        $failedDetails = [];

        foreach ($ids as $id) {
            $izin = $izinMap->get($id);

            if (! $izin) {
                $failedIds[] = $id;
                $failedDetails[] = [
                    'id_izin' => $id,
                    'reason' => 'Pengajuan izin tidak ditemukan atau tidak dapat diverifikasi.',
                ];
                continue;
            }

            if ($aksi === 'tandai_lengkap' && $izin->jenisIzin->wajib_dokumen && $izin->dokumen->isEmpty()) {
                $failedIds[] = $id;
                $failedDetails[] = [
                    'id_izin' => $id,
                    'reason' => 'Jenis izin wajib dokumen, tetapi belum ada file yang diunggah.',
                ];
                continue;
            }

            try {
                $sebelum = $izin->toArray();

                if ($aksi === 'tandai_lengkap') {
                    $izin->update([
                        'status_dokumen' => PengajuanIzin::DOKUMEN_LENGKAP,
                        'catatan_dokumen' => null,
                        'diverifikasi_hr' => $hr->id_pengguna,
                        'waktu_verifikasi_hr' => now(),
                    ]);
                } else {
                    $izin->update([
                        'status_dokumen' => PengajuanIzin::DOKUMEN_TIDAK_LENGKAP,
                        'catatan_dokumen' => $catatan,
                        'diverifikasi_hr' => $hr->id_pengguna,
                        'waktu_verifikasi_hr' => now(),
                    ]);

                    $this->notifikasiDokumenTidakLengkap($izin, $hr->id_pengguna);
                }

                AuditLogService::catat(
                    pengguna: $hr,
                    jenis: AuditLog::JENIS_IZIN,
                    idReferensi: $izin->id_izin,
                    aksi: AuditLog::AKSI_UPDATE,
                    catatan: "HR bulk verifikasi dokumen ({$aksi}): {$izin->karyawan->nama_lengkap}" . ($catatan ? " - {$catatan}" : ''),
                    sebelum: $sebelum,
                    sesudah: $izin->fresh()->toArray(),
                );

                $totalSuccess++;
            } catch (\Throwable $e) {
                $failedIds[] = $id;
                $failedDetails[] = [
                    'id_izin' => $id,
                    'reason' => 'Terjadi kesalahan saat memproses pengajuan.',
                ];
            }
        }

        $totalFailed = count($failedIds);
        $status = $totalSuccess > 0;

        if ($totalSuccess > 0 && $totalFailed > 0) {
            $message = "Bulk verifikasi selesai parsial: {$totalSuccess} berhasil, {$totalFailed} gagal.";
        } elseif ($totalSuccess > 0) {
            $message = "Bulk verifikasi berhasil untuk {$totalSuccess} pengajuan.";
        } else {
            $message = 'Bulk verifikasi gagal diproses.';
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => [
                'total_success' => $totalSuccess,
                'total_failed' => $totalFailed,
                'failed_ids' => $failedIds,
                'failed_items' => $failedDetails,
            ],
        ], $status ? 200 : 422);
    }

    public function streamDokumen(int $id, int $docId): JsonResponse|StreamedResponse
    {
        $izin = PengajuanIzin::where('status', PengajuanIzin::STATUS_DISETUJUI);
        $this->applyDokumenVerifikasiScope($izin);
        $izin = $izin->find($id);

        if (! $izin) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan izin tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        $dokumen = DokumenIzin::where('id_dokumen', $docId)
            ->where('id_izin', $izin->id_izin)
            ->first();

        if (! $dokumen || ! Storage::exists($dokumen->path_file)) {
            return response()->json([
                'status' => false,
                'message' => 'File dokumen tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        $mimeMap = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        $mime = $mimeMap[strtolower($dokumen->tipe_file)] ?? 'application/octet-stream';
        $isInline = in_array(strtolower($dokumen->tipe_file), ['pdf', 'jpg', 'jpeg', 'png']);
        $disposition = $isInline ? 'inline' : 'attachment';

        return Storage::response(
            $dokumen->path_file,
            $dokumen->nama_file,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => "{$disposition}; filename=\"{$dokumen->nama_file}\"",
            ]
        );
    }

    private function notifikasiDokumenTidakLengkap(PengajuanIzin $izin, int $idPengirim): void
    {
        $idKaryawanPengguna = $izin->karyawan?->id_pengguna;
        if (! $idKaryawanPengguna) {
            return;
        }

        NotifikasiService::kirim(
            idPenerima: $idKaryawanPengguna,
            judul: "Dokumen izin Anda belum lengkap",
            isi: "HR menemukan kekurangan dokumen pada pengajuan izin Anda"
                . ($izin->catatan_dokumen ? ". Catatan: {$izin->catatan_dokumen}" : '.'),
            jenis: Notifikasi::JENIS_IZIN,
            idPengirim: $idPengirim,
            idReferensi: $izin->id_izin,
        );
    }

    /**
     * Scope dokumen untuk modul verifikasi HR:
     * - Jenis izin tidak wajib dokumen: tetap ditampilkan.
     * - Jenis izin wajib dokumen: ditampilkan jika sudah ada file dokumen,
     *   termasuk setelah status berubah menjadi lengkap/tidak_lengkap.
     */
    private function applyDokumenVerifikasiScope($query): void
    {
        $query->where(function ($q) {
            $q->whereHas('jenisIzin', fn($jenis) => $jenis->where('wajib_dokumen', false))
                ->orWhere(function ($wajib) {
                    $wajib->whereHas('jenisIzin', fn($jenis) => $jenis->where('wajib_dokumen', true))
                        ->whereHas('dokumen');
                });
        });
    }

    private function formatIzin(PengajuanIzin $i, bool $detail = false): array
    {
        $tanggalMulai = $i->tanggal_izin;
        $tanggalSelesai = $i->tanggal_selesai_izin ?? $i->tanggal_izin;
        $jumlahHari = (int) $tanggalMulai->diffInDays($tanggalSelesai) + 1;

        $base = [
            'id_izin' => $i->id_izin,
            'tanggal_izin' => $i->tanggal_izin?->format('Y-m-d'),
            'tanggal_selesai_izin' => $i->tanggal_selesai_izin?->format('Y-m-d'),
            'jumlah_hari' => $jumlahHari,
            'karyawan' => $i->karyawan ? [
                'id_karyawan' => $i->karyawan->id_karyawan,
                'nama_lengkap' => $i->karyawan->nama_lengkap,
                'nomor_karyawan' => $i->karyawan->nomor_karyawan,
                'departemen' => $i->karyawan->departemen?->nama_departemen,
                'perusahaan' => $i->karyawan->perusahaan?->nama_perusahaan,
            ] : null,
            'jenis_izin' => $i->jenisIzin ? [
                'nama_jenis' => $i->jenisIzin->nama_jenis,
                'wajib_dokumen' => $i->jenisIzin->wajib_dokumen,
            ] : null,
            'status' => $i->status,
            'status_dokumen' => $i->status_dokumen,
            'catatan_dokumen' => $i->catatan_dokumen,
            'jumlah_dokumen' => $i->dokumen?->count() ?? 0,
            'diajukan_pada' => $i->diajukan_pada?->toDateTimeString(),
            'waktu_validasi_admin' => $i->waktu_validasi_admin?->toDateTimeString(),
            'waktu_verifikasi_hr' => $i->waktu_verifikasi_hr?->toDateTimeString(),
        ];

        if ($detail) {
            $base['karyawan']['posisi'] = $i->karyawan?->posisi;
            $base['karyawan']['kode_departemen'] = $i->karyawan?->departemen?->kode_departemen;
            $base['keterangan'] = $i->keterangan;
            $base['catatan_penolakan'] = $i->catatan_penolakan;
            $base['validator_admin'] = $i->validatorAdmin?->nama_lengkap;
            $base['jenis_izin']['keterangan'] = $i->jenisIzin?->keterangan;

            $base['dokumen'] = $i->dokumen?->map(fn($d) => [
                'id_dokumen' => $d->id_dokumen,
                'nama_file' => $d->nama_file,
                'tipe_file' => $d->tipe_file,
                'ukuran_kb' => $d->ukuran_kb,
                'diunggah_pada' => $d->diunggah_pada?->toDateTimeString(),
            ])->values()->all() ?? [];
        }

        return $base;
    }
}
