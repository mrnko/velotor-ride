<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
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
        $this->applyTimezoneSetting();
    }

    /**
     * The admin panel lets the timezone be changed from the `settings`
     * table; apply it over the .env default so every date calculation
     * (week windows, the Monday close job, displayed dates) stays
     * consistent without needing a deploy. Guarded so artisan commands
     * that run before the `settings` table exists (fresh install,
     * migrations themselves) don't break.
     */
    private function applyTimezoneSetting(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $timezone = Setting::get('timezone');

            if ($timezone) {
                config(['app.timezone' => $timezone, 'velotor.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (\Throwable) {
            // No database configured yet (e.g. very first artisan call) — fall back to .env default.
        }
    }
}
