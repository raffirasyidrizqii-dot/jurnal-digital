<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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
        // Rate Limiter DI-NONAKTIFKAN (permintaan user 2026-12-01, saat presentasi).
        // Login & forgot-password route sudah tanpa middleware throttle.
        // Limiter 'login'/'api' dibiarkan terdaftar dgn batas besar agar referensi tidak error.
        RateLimiter::for('login', function (Request $request) {
            return Limit::none();
        });

        // Global API limiter (efektif tanpa batas praktis)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(1000000)->by($request->user()?->id ?: $request->ip());
        });
    }
}
