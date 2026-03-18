<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Handler
 *
 * Override render() agar semua exception yang terjadi di route api/*
 * selalu mengembalikan JSON dengan format konsisten, bukan halaman HTML error.
 *
 * Format response: { "status": false, "message": "...", "data": null }
 */
class Handler extends ExceptionHandler
{
    /**
     * Exception yang tidak perlu di-report ke log.
     */
    protected $dontReport = [
        //
    ];

    /**
     * Field yang tidak di-flash ke session saat validasi gagal.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_hash',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render exception ke HTTP response.
     *
     * Untuk semua request ke api/*, selalu return JSON.
     * Untuk request web biasa, delegasikan ke parent (Blade error page).
     */
    public function render($request, Throwable $e): \Symfony\Component\HttpFoundation\Response
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->renderApiException($e);
        }

        return parent::render($request, $e);
    }

    /**
     * Konversi exception ke JSON response dengan format konsisten.
     */
    private function renderApiException(Throwable $e): JsonResponse
    {
        // Validasi gagal (dari FormRequest atau manual validate())
        if ($e instanceof ValidationException) {
            return response()->json([
                'status'  => false,
                'message' => 'Data yang dikirim tidak valid.',
                'data'    => $e->errors(),
            ], 422);
        }

        // Belum login / session expired
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'status'  => false,
                'message' => 'Silakan login terlebih dahulu.',
                'data'    => null,
            ], 401);
        }

        // HTTP exception (403 Forbidden, 404 Not Found, dsb.)
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $message    = $this->getHttpMessage($statusCode, $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => $message,
                'data'    => null,
            ], $statusCode);
        }

        // Semua exception lain → 500, jangan expose detail error di production
        $message = app()->isProduction()
            ? 'Terjadi kesalahan pada server. Silakan coba lagi nanti.'
            : $e->getMessage();

        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => null,
        ], 500);
    }

    /**
     * Pesan default untuk HTTP status code umum.
     */
    private function getHttpMessage(int $statusCode, string $originalMessage): string
    {
        if (! empty($originalMessage)) {
            return $originalMessage;
        }

        return match ($statusCode) {
            400     => 'Permintaan tidak valid.',
            401     => 'Autentikasi diperlukan.',
            403     => 'Anda tidak memiliki akses ke resource ini.',
            404     => 'Data atau halaman tidak ditemukan.',
            405     => 'Metode HTTP tidak diizinkan.',
            422     => 'Data yang dikirim tidak dapat diproses.',
            429     => 'Terlalu banyak permintaan. Coba lagi nanti.',
            500     => 'Terjadi kesalahan pada server.',
            default => 'Terjadi kesalahan.',
        };
    }
}
