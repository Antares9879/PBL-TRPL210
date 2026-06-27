<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Railway (dan PaaS sejenis) terminate TLS di edge, lalu forward ke
        // container via HTTP biasa secara internal. Daripada bergantung
        // sepenuhnya pada parsing header X-Forwarded-Proto di sepanjang chain
        // nginx → php-fpm → Symfony TrustProxies, paksa langsung semua URL
        // yang Laravel generate (asset(), @vite(), route(), redirect()) pakai
        // https saat di production — supaya tidak ada mixed content lagi.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}