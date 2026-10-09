<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Reminder: per-student bucket para hindi mag-share ang buong klase sa iisang school IP
        \Illuminate\Support\Facades\RateLimiter::for('portal-verify', function (\Illuminate\Http\Request $request) {
            $sn = (string) $request->input('student_number', $request->input('student_id', 'none'));

            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('sn:' . $sn . '|' . $request->ip()),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(200)->by('ip:' . $request->ip()),
            ];
        });

        Vite::prefetch(concurrency: 3);

        // Force HTTPS scheme sa production para hindi mixed content
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}