<?php

namespace App\Http\Middleware;

use App\Models\Pengguna;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleMiddleware
 *
 * Memastikan pengguna yang sudah login memiliki role yang sesuai
 * untuk mengakses route tertentu.
 *
 * Penggunaan di routes:
 *   Route::middleware('role:super_admin')
 *   Route::middleware('role:hr,admin_outsource')   ← multiple role dipisah koma
 *
 * Perilaku saat akses ditolak:
 *   - Request dari AJAX / API  → JSON 403
 *   - Request dari browser     → redirect ke /login atau /dashboard
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Pastikan pengguna sudah login
        if (! Auth::check()) {
            return $this->unauthorizedResponse($request);
        }

        /** @var Pengguna $pengguna */
        $pengguna = Auth::user();

        // Pastikan akun masih aktif (double-check, bisa saja dinonaktifkan setelah login)
        if (! $pengguna->isAktif()) {
            Auth::logout();
            $request->session()->invalidate();

            return $this->unauthorizedResponse($request, 'Akun Anda telah dinonaktifkan.');
        }

        // Cek apakah role pengguna termasuk dalam daftar role yang diizinkan
        if (! in_array($pengguna->role, $roles, strict: true)) {
            return $this->forbiddenResponse($request, $pengguna);
        }

        return $next($request);
    }

    /**
     * Response untuk pengguna yang belum login (401).
     */
    private function unauthorizedResponse(Request $request, string $message = 'Silakan login terlebih dahulu.'): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status'  => false,
                'message' => $message,
                'data'    => null,
            ], 401);
        }

        return redirect()->route('login');
    }

    /**
     * Response untuk pengguna yang sudah login tapi role tidak sesuai (403).
     * Redirect ke dashboard role mereka sendiri agar tidak bingung.
     */
    private function forbiddenResponse(Request $request, Pengguna $pengguna): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status'  => false,
                'message' => 'Anda tidak memiliki akses ke halaman ini.',
                'data'    => null,
            ], 403);
        }

        // Redirect ke dashboard role yang sesuai (bukan halaman error)
        return redirect($pengguna->getDashboardUrl());
    }
}
