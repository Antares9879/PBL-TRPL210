<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotifikasiApiController
 *
 * Mengelola notifikasi in-app untuk pengguna yang sedang login.
 * Controller ini bersifat shared — digunakan oleh semua role.
 * Setiap pengguna hanya bisa mengakses notifikasinya sendiri.
 *
 * Endpoints (semua di bawah prefix /api/notifikasi):
 *   GET   /api/notifikasi              → index()       — daftar notifikasi (paginasi)
 *   GET   /api/notifikasi/jumlah-baru  → jumlahBaru()  — badge count (tidak dibaca)
 *   PATCH /api/notifikasi/{id}/baca    → tandaiBaca()  — tandai satu sebagai dibaca
 *   PATCH /api/notifikasi/baca-semua  → bacaSemua()   — tandai semua sebagai dibaca
 */
class NotifikasiApiController extends Controller
{
    // ════════════════════════════════════════════════════════════════════════
    //  INDEX — Daftar notifikasi pengguna yang login
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Daftar notifikasi pengguna yang sedang login, dengan paginasi.
     *
     * Filter opsional:
     *   - is_dibaca (true/false) — default tampilkan semua
     *   - jenis (absensi/lembur/izin/planning/sistem)
     */
    public function index(Request $request): JsonResponse
    {
        $idPengguna = auth()->id();

        $query = Notifikasi::where('id_penerima', $idPengguna);

        if ($request->filled('is_dibaca')) {
            $query->where('is_dibaca', filter_var($request->is_dibaca, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $data = $query
            ->orderBy('is_dibaca')           // belum dibaca duluan
            ->orderByDesc('created_at')
            ->paginate(20);

        $data->getCollection()->transform(fn ($n) => $this->formatNotifikasi($n));

        return response()->json([
            'status'  => true,
            'message' => 'Notifikasi berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  JUMLAH BARU — Badge count
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Jumlah notifikasi yang belum dibaca.
     * Digunakan untuk badge/counter di navbar. Endpoint ringan — tidak paginasi.
     */
    public function jumlahBaru(): JsonResponse
    {
        $jumlah = Notifikasi::where('id_penerima', auth()->id())
            ->where('is_dibaca', false)
            ->count();

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => ['jumlah_belum_dibaca' => $jumlah],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  TANDAI BACA — Satu notifikasi
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Tandai satu notifikasi sebagai dibaca.
     * Idempotent — aman dipanggil berulang kali.
     */
    public function tandaiBaca(int $id): JsonResponse
    {
        $notifikasi = Notifikasi::where('id_notifikasi', $id)
            ->where('id_penerima', auth()->id())
            ->first();

        if (! $notifikasi) {
            return response()->json([
                'status'  => false,
                'message' => 'Notifikasi tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if (! $notifikasi->is_dibaca) {
            $notifikasi->update([
                'is_dibaca'  => true,
                'dibaca_pada'=> now(),
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Notifikasi ditandai sebagai dibaca.',
            'data'    => $this->formatNotifikasi($notifikasi->fresh()),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  BACA SEMUA
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Tandai semua notifikasi belum dibaca milik pengguna yang login sebagai dibaca.
     * Mengembalikan jumlah notifikasi yang diperbarui.
     */
    public function bacaSemua(): JsonResponse
    {
        $jumlahDiperbarui = Notifikasi::where('id_penerima', auth()->id())
            ->where('is_dibaca', false)
            ->update([
                'is_dibaca'  => true,
                'dibaca_pada'=> now(),
            ]);

        return response()->json([
            'status'  => true,
            'message' => "Semua notifikasi telah ditandai sebagai dibaca.",
            'data'    => ['jumlah_diperbarui' => $jumlahDiperbarui],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Helpers
    // ════════════════════════════════════════════════════════════════════════

    private function formatNotifikasi(Notifikasi $n): array
    {
        return [
            'id_notifikasi' => $n->id_notifikasi,
            'judul'         => $n->judul,
            'isi'           => $n->isi,
            'jenis'         => $n->jenis,
            'id_referensi'  => $n->id_referensi,
            'is_dibaca'     => $n->is_dibaca,
            'dibaca_pada'   => $n->dibaca_pada?->toDateTimeString(),
            'created_at'    => $n->created_at?->toDateTimeString(),
        ];
    }
}