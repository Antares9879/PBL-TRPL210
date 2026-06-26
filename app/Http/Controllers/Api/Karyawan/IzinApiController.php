<?php

namespace App\Http\Controllers\Api\Karyawan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Karyawan\StoreIzinRequest;
use App\Http\Requests\Karyawan\UploadDokumenRequest;
use App\Models\DokumenIzin;
use App\Models\JenisIzin;
use App\Models\PengajuanIzin;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * IzinApiController — F04, F05
 *
 * F04: Pengajuan izin tidak masuk dari karyawan (single-day & multi-day).
 * F05: Upload dokumen pendukung (surat dokter, surat undangan, dll.).
 *
 * Alur pengajuan izin:
 *   1. Karyawan isi form (jenis izin, tanggal mulai, tanggal selesai, keterangan).
 *   2. Satu record = satu pengajuan untuk seluruh range tanggal.
 *   3. Jika tanggal_selesai_izin tidak diisi, dianggap izin 1 hari.
 *   4. Jika jenis izin wajib_dokumen = true, karyawan upload dokumen.
 *   5. Admin Outsource menerima notifikasi dan memvalidasi (F10).
 *
 * Cek overlap: ditolak jika ada pengajuan aktif yang tanggalnya overlap dengan range baru.
 *
 * Endpoints:
 *   GET    /api/karyawan/izin                          → index()
 *   POST   /api/karyawan/izin                          → store()   F04
 *   GET    /api/karyawan/izin/{id}                     → show()
 *   POST   /api/karyawan/izin/{id}/dokumen             → uploadDokumen() F05
 *   GET    /api/karyawan/izin/{id}/dokumen/{docId}     → downloadDokumen()
 *   GET    /api/karyawan/jenis-izin                    → jenisIzin() — lookup
 */
class IzinApiController extends Controller
{
    // ── F04 — PENGAJUAN IZIN ──────────────────────────────────────────────────

    /**
     * Daftar pengajuan izin milik karyawan yang login.
     */
    public function index(Request $request): JsonResponse
    {
        $karyawan = Auth::user()->karyawan;

        if (! $karyawan) {
            return response()->json([
                'status'  => false,
                'message' => 'Data karyawan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $query = PengajuanIzin::with([
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen:id_dokumen,id_izin,nama_file,tipe_file,ukuran_kb',
        ])
        ->where('id_karyawan', $karyawan->id_karyawan);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
     * Buat pengajuan izin baru (single-day atau multi-day).
     *
     * Logika tanggal:
     *   - Jika tanggal_selesai_izin tidak dikirim/null → izin 1 hari, tanggal_selesai = tanggal_izin
     *   - Cek overlap: tidak boleh ada pengajuan aktif yang tanggalnya bersinggungan
     */
    public function store(StoreIzinRequest $request): JsonResponse
    {
        $karyawan = Auth::user()->karyawan;
        $pengguna = Auth::user();

        if (! $karyawan) {
            return response()->json([
                'status'  => false,
                'message' => 'Data karyawan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        // Normalisasi tanggal selesai: jika tidak diisi, sama dengan tanggal mulai
        $tanggalMulai   = $request->tanggal_izin;
        $tanggalSelesai = $request->tanggal_selesai_izin ?? $tanggalMulai;

        // Cek overlap: apakah ada pengajuan aktif yang tanggalnya bersinggungan?
        $sudahAda = PengajuanIzin::where('id_karyawan', $karyawan->id_karyawan)
            ->whereIn('status', [PengajuanIzin::STATUS_MENUNGGU, PengajuanIzin::STATUS_DISETUJUI])
            ->overlapDengan($tanggalMulai, $tanggalSelesai)
            ->exists();

        if ($sudahAda) {
            return response()->json([
                'status'  => false,
                'message' => 'Sudah ada pengajuan izin aktif yang tanggalnya bertabrakan dengan periode yang Anda pilih.',
                'data'    => null,
            ], 422);
        }

        $jenisIzin = JenisIzin::find($request->id_jenis_izin);

        $izin = PengajuanIzin::create([
            'id_karyawan'          => $karyawan->id_karyawan,
            'id_jenis_izin'        => $request->id_jenis_izin,
            'tanggal_izin'         => $tanggalMulai,
            'tanggal_selesai_izin' => $tanggalSelesai,
            'keterangan'           => $request->keterangan,
            'status'               => PengajuanIzin::STATUS_MENUNGGU,
            'status_dokumen'       => PengajuanIzin::DOKUMEN_BELUM_UPLOAD,
            'diajukan_pada'        => now(),
        ]);

        // Notifikasi ke Admin Outsource
        $adminPengguna = $karyawan->perusahaan
            ->adminProfiles()
            ->with('pengguna:id_pengguna')
            ->get()
            ->pluck('pengguna.id_pengguna');

        // Buat label periode untuk notifikasi
        $periodeLabel = $tanggalMulai === $tanggalSelesai
            ? \Carbon\Carbon::parse($tanggalMulai)->format('d M Y')
            : \Carbon\Carbon::parse($tanggalMulai)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($tanggalSelesai)->format('d M Y');

        foreach ($adminPengguna as $idAdmin) {
            NotifikasiService::kirim(
                idPenerima:  $idAdmin,
                judul:       "{$karyawan->nama_lengkap} mengajukan izin {$jenisIzin->nama_jenis}",
                isi:         "Pengajuan izin {$jenisIzin->nama_jenis} pada {$periodeLabel}. Menunggu validasi Anda.",
                jenis:       \App\Models\Notifikasi::JENIS_IZIN,
                idPengirim:  $pengguna->id_pengguna,
                idReferensi: $izin->id_izin,
            );
        }

        $izin->load('jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen');

        $pesan = 'Pengajuan izin berhasil dikirim.';
        if ($jenisIzin->wajib_dokumen) {
            $pesan .= ' Segera unggah dokumen pendukung agar izin dapat diproses.';
        }

        return response()->json([
            'status'  => true,
            'message' => $pesan,
            'data'    => $this->formatIzin($izin),
        ], 201);
    }

    /**
     * Detail satu pengajuan izin.
     */
    public function show(int $id): JsonResponse
    {
        $karyawan = Auth::user()->karyawan;

        $izin = PengajuanIzin::with([
            'jenisIzin:id_jenis_izin,nama_jenis,wajib_dokumen',
            'dokumen:id_dokumen,id_izin,nama_file,tipe_file,ukuran_kb,diunggah_pada',
        ])
        ->where('id_izin', $id)
        ->where('id_karyawan', $karyawan?->id_karyawan)
        ->first();

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
            'data'    => $this->formatIzin($izin),
        ]);
    }

    // ── F05 — UPLOAD DOKUMEN ──────────────────────────────────────────────────

    /**
     * Upload dokumen pendukung untuk satu pengajuan izin.
     */
    public function uploadDokumen(UploadDokumenRequest $request, int $id): JsonResponse
    {
        $karyawan = Auth::user()->karyawan;
        $pengguna = Auth::user();

        $izin = PengajuanIzin::where('id_izin', $id)
            ->where('id_karyawan', $karyawan?->id_karyawan)
            ->first();

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
                'message' => 'Dokumen tidak dapat diunggah karena pengajuan izin sudah diproses.',
                'data'    => null,
            ], 422);
        }

        $file     = $request->file('dokumen');
        $namaAsli = $file->getClientOriginalName();
        $ekstensi = $file->getClientOriginalExtension();
        $ukuranKb = (int) ceil($file->getSize() / 1024);

        try {
            $fileName = pathinfo($namaAsli, PATHINFO_FILENAME) . '_' . time() . '.' . $ekstensi;
            $folderPath = "dokumen-izin/{$izin->id_izin}";
            
            $uploadedFile = Storage::disk('cloudinary')->putFileAs($folderPath, $file, $fileName);

            $cloudName = config('filesystems.disks.cloudinary.cloud_name');
            $resourceType = 'raw'; // sesuai mapping pdf di resource_types
            $secureUrl = "https://res.cloudinary.com/{$cloudName}/{$resourceType}/upload/{$uploadedFile}";

            $dokumen = DokumenIzin::create([
                'id_izin'              => $izin->id_izin,
                'nama_file'            => $namaAsli,
                'path_file'            => $secureUrl,
                'cloudinary_public_id' => $uploadedFile,
                'tipe_file'            => strtolower($ekstensi),
                'ukuran_kb'            => $ukuranKb,
                'diunggah_oleh'        => $pengguna->id_pengguna,
                'diunggah_pada'        => now(),
            ]);

            $izin->update(['status_dokumen' => PengajuanIzin::DOKUMEN_SUDAH_UPLOAD]);

            return response()->json([
                'status'  => true,
                'message' => 'Dokumen berhasil diunggah.',
                'data'    => [
                    'id_dokumen'   => $dokumen->id_dokumen,
                    'nama_file'    => $dokumen->nama_file,
                    'tipe_file'    => $dokumen->tipe_file,
                    'ukuran_kb'    => $dokumen->ukuran_kb,
                    'diunggah_pada'=> $dokumen->diunggah_pada->toDateTimeString(),
                ],
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Upload dokumen izin gagal', [
                'id_izin' => $izin->id_izin,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengunggah dokumen: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Download / stream dokumen izin.
     */
    public function downloadDokumen(int $id, int $docId): JsonResponse
    {
        $karyawan = Auth::user()->karyawan;

        $izin = PengajuanIzin::where('id_izin', $id)
            ->where('id_karyawan', $karyawan?->id_karyawan)
            ->first();

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

        if (! $dokumen || ! $dokumen->path_file) {
            return response()->json([
                'status'  => false,
                'message' => 'File dokumen tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        // path_file sekarang adalah Cloudinary URL — kembalikan langsung ke client
        return response()->json([
            'status'  => true,
            'message' => 'URL dokumen berhasil dimuat.',
            'data'    => [
                'url'       => $dokumen->path_file,
                'nama_file' => $dokumen->nama_file,
                'tipe_file' => $dokumen->tipe_file,
            ],
        ]);
    }

    /**
     * Lookup jenis izin — digunakan untuk populate dropdown form.
     */
    public function jenisIzin(): JsonResponse
    {
        $data = JenisIzin::orderBy('nama_jenis')->get(['id_jenis_izin', 'nama_jenis', 'wajib_dokumen', 'keterangan']);

        return response()->json([
            'status'  => true,
            'message' => 'Data jenis izin berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    // ── HELPER ────────────────────────────────────────────────────────────────

    private function formatIzin(PengajuanIzin $i): array
    {
        // Hitung jumlah hari (inklusif, handle null tanggal_selesai)
        $tanggalMulai   = $i->tanggal_izin;
        $tanggalSelesai = $i->tanggal_selesai_izin ?? $i->tanggal_izin;
        $jumlahHari     = (int) $tanggalMulai->diffInDays($tanggalSelesai) + 1;

        return [
            'id_izin'              => $i->id_izin,
            'tanggal_izin'         => $i->tanggal_izin?->format('Y-m-d'),
            'tanggal_selesai_izin' => $i->tanggal_selesai_izin?->format('Y-m-d'),
            'jumlah_hari'          => $jumlahHari,
            'jenis_izin'           => $i->jenisIzin ? [
                'nama_jenis'    => $i->jenisIzin->nama_jenis,
                'wajib_dokumen' => $i->jenisIzin->wajib_dokumen,
            ] : null,
            'keterangan'           => $i->keterangan,
            'status'               => $i->status,
            'catatan_penolakan'    => $i->catatan_penolakan,
            'status_dokumen'       => $i->status_dokumen,
            'jumlah_dokumen'       => $i->dokumen?->count() ?? 0,
            'dokumen'              => $i->dokumen?->map(fn($d) => [
                'id_dokumen'   => $d->id_dokumen,
                'nama_file'    => $d->nama_file,
                'tipe_file'    => $d->tipe_file,
                'ukuran_kb'    => $d->ukuran_kb,
                'diunggah_pada'=> $d->diunggah_pada?->toDateTimeString(),
            ])->values() ?? [],
            'diajukan_pada'        => $i->diajukan_pada?->toDateTimeString(),
        ];
    }
}