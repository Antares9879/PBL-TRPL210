<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Pengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * AuthApiController
 *
 * Menangani seluruh proses autentikasi via AJAX (JSON response).
 * Menggunakan Laravel Session (guard 'web') agar kompatibel dengan
 * Blade + AJAX pattern yang dipakai proyek ini.
 *
 * Endpoint:
 *   POST /api/auth/login   → login()
 *   POST /api/auth/logout  → logout()
 *   GET  /api/auth/me      → me()
 */
class AuthApiController extends Controller
{
    /**
     * Batas maksimal percobaan login yang gagal sebelum rate-limited.
     * Menggunakan identifier: kombinasi email + IP.
     */
    private const MAX_ATTEMPTS  = 5;
    private const DECAY_SECONDS = 60;

    // ──────────────────────────────────────────────────────────────────────────
    //  LOGIN
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Proses login via AJAX.
     *
     * Flow:
     *   1. Cek rate limit
     *   2. Cari pengguna berdasarkan email
     *   3. Verifikasi password (bcrypt)
     *   4. Cek status akun (aktif/nonaktif)
     *   5. Buat session Laravel
     *   6. Update last_login
     *   7. Return redirect URL berdasarkan role
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $throttleKey = $this->buildThrottleKey($request);

        // ── 1. Rate limiting ──────────────────────────────────────────────────
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'status'  => false,
                'message' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
                'data'    => null,
            ], 429);
        }

        // ── 2. Cari pengguna berdasarkan email ────────────────────────────────
        $pengguna = Pengguna::where('email', $request->email)->first();

        // ── 3. Verifikasi password ────────────────────────────────────────────
        if (! $pengguna || ! Hash::check($request->password, $pengguna->password_hash)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            // Gunakan pesan generik untuk menghindari user enumeration attack
            return response()->json([
                'status'  => false,
                'message' => 'Email atau password tidak valid.',
                'data'    => null,
            ], 401);
        }

        // ── 4. Cek status akun ────────────────────────────────────────────────
        if (! $pengguna->isAktif()) {
            return response()->json([
                'status'  => false,
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
                'data'    => null,
            ], 403);
        }

        // ── 5. Login via Laravel Auth (membuat session) ───────────────────────
        Auth::login($pengguna, $request->boolean('remember'));

        // ── 6. Update last_login & regenerate session (anti session fixation) ─
        $request->session()->regenerate();
        $pengguna->update(['last_login' => now()]);

        // ── 7. Bersihkan rate limit counter ──────────────────────────────────
        RateLimiter::clear($throttleKey);

        // ── 8. Log aksi login (opsional, non-blocking) ───────────────────────
        Log::info('Login berhasil', [
            'id_pengguna' => $pengguna->id_pengguna,
            'role'        => $pengguna->role,
            'ip'          => $request->ip(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Login berhasil.',
            'data'    => [
                'redirect'     => $pengguna->getDashboardUrl(),
                'role'         => $pengguna->role,
                'nama_lengkap' => $pengguna->nama_lengkap,
            ],
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  LOGOUT
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Logout pengguna dan invalidasi session.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status'  => true,
            'message' => 'Anda telah keluar dari sistem.',
            'data'    => ['redirect' => '/login'],
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  ME (cek session aktif)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Mengembalikan data pengguna yang sedang login.
     * Digunakan JS untuk cek apakah session masih valid.
     *
     * @middleware auth
     */
    public function me(Request $request): JsonResponse
    {
        /** @var Pengguna $pengguna */
        $pengguna = Auth::user();

        return response()->json([
            'status'  => true,
            'message' => 'Session aktif.',
            'data'    => [
                'id_pengguna'  => $pengguna->id_pengguna,
                'nama_lengkap' => $pengguna->nama_lengkap,
                'email'        => $pengguna->email,
                'role'         => $pengguna->role,
                'last_login'   => $pengguna->last_login?->toIso8601String(),
            ],
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Bangun throttle key unik per kombinasi email + IP.
     * Lowercase email untuk menghindari duplikasi key.
     */
    private function buildThrottleKey(Request $request): string
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }
}
