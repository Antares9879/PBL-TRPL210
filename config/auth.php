<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | Guard default: 'web' menggunakan session Laravel.
    | Sanctum akan digunakan secara otomatis untuk API token (karyawan mobile).
    |
    */

    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'pengguna',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | 'web'  → Session-based, digunakan oleh semua role yang akses via browser.
    |          Sanctum secara otomatis menangani token-based untuk API request
    |          yang menyertakan Bearer token.
    |
    */

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'pengguna',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Provider 'pengguna' memetakan ke model App\Models\Pengguna.
    | Kolom password di schema kita bernama 'password_hash', namun
    | Laravel Auth menggunakan getAuthPassword() yang sudah di-override
    | di model Pengguna untuk mengembalikan nilai kolom 'password_hash'.
    |
    */

    'providers' => [
        'pengguna' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Pengguna::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Configuration
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'pengguna' => [
            'provider' => 'pengguna',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => 10800,

];
