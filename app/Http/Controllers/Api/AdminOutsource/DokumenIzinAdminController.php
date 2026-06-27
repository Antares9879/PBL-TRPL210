<?php

namespace App\Http\Controllers\Api\AdminOutsource;

use App\Http\Controllers\Controller;
use App\Models\DokumenIzin;
use App\Models\Pengguna;
use App\Models\PengajuanIzin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
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
        // Hapus ->where('status', STATUS_MENUNGGU)
        // Scope hanya berdasarkan karyawan dari perusahaan admin ini
        $izin = PengajuanIzin::whereHas(
            'karyawan',
            fn($q) => $q->where('id_perusahaan', $this->getIdPerusahaan())
        )
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

        if (! $dokumen || ! $dokumen->path_file) {
            return response()->json([
                'status'  => false,
                'message' => 'File dokumen tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

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
}
