<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

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
        // HTTPS public domain par forms/assets https rahen, lekin local testing par
        // form action zabardasti ngrok domain par na jaye warna CSRF/session 419 aa sakta hai.
        $appUrl = (string) config('app.url');
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $currentHost = request()->getHost();

        if ($appHost && $currentHost === $appHost && Str::startsWith($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
