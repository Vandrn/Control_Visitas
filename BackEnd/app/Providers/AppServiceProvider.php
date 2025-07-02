<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;


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
public function boot()
{
    if (app()->environment('production')) {
        $manifestPath = base_path('../public_html/retail-dashboard/build/manifest.json');
        app()->bind('vite.manifest.path', function () use ($manifestPath) {
            return $manifestPath;
        });
    }
}

}

