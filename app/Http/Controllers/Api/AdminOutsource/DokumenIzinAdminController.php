<?php

namespace App\Http\Controllers\Api\AdminOutsource;

use App\Http\Controllers\Controller;
use App\Models\DokumenIzin;
use App\Models\Pengguna;
use App\Models\PengajuanIzin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

/**
 * DokumenIzinAdminController
 *
 * Memungkinkan Admin Outsource mengakses dan men-stream dokumen pendukung
 * pengajuan izin dari karyawan yang dikelolanya.
 *
 * Scope: Admin hanya bisa mengakses dokumen dari pengajuan izin
 * karyawan yang tergabung dalam perusahaannya.
 *
 * Endpoints:
 *   GET /api/admin/izin/{id}/dokumen/{docId} → stream()
 */
class DokumenIzinAdminController extends Controller
{
    /**
     * Ambil id_perusahaan Admin yang sedang login.
     */
    private function getIdPerusahaan(): int
    {
        return $this->authenticatedPengguna()->adminOutsourceProfile->id_perusahaan;
    }

    private function authenticatedPengguna(): Pengguna
    {
        $user = Auth::user();

        if (! $user instanceof Pengguna) {
            throw new AuthenticationException('Pengguna tidak terautentikasi.');
        }

        return $user;
    }

    /**
     * Stream file dokumen ke browser.
     *
     * Untuk PDF  → Content-Disposition: inline (bisa preview di browser)
     * Untuk gambar → Content-Disposition: inline
     * Lainnya   → Content-Disposition: attachment (force download)
     *
     * Guard: Admin hanya bisa akses dokumen dari karyawan perusahaannya.
     */
    public function stream(int $id, int $docId): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Scope izin yang masih tahap validasi Admin:
        // - status izin masih menunggu
        // - jika wajib dokumen, status dokumen harus sudah_upload dan file sudah ada
        $izin = PengajuanIzin::whereHas(
            'karyawan',
            fn($q) => $q->where('id_perusahaan', $this->getIdPerusahaan())
        )
        ->where('status', PengajuanIzin::STATUS_MENUNGGU)
        ->where(function ($q) {
            $q->whereHas('jenisIzin', fn($jenis) => $jenis->where('wajib_dokumen', false))
                ->orWhere(function ($wajib) {
                    $wajib->whereHas('jenisIzin', fn($jenis) => $jenis->where('wajib_dokumen', true))
                        ->where('status_dokumen', PengajuanIzin::DOKUMEN_SUDAH_UPLOAD)
                        ->whereHas('dokumen');
                });
        })
        ->find($id);

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

        // Tentukan MIME type dari ekstensi file
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
}
