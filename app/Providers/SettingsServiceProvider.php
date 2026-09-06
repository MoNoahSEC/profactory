<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
            
            // Set default if not exists
            if (!isset($settings['company_name'])) {
                $settings['company_name'] = 'شركة سالم علي - مصنع المصرية';
            }
            
            \Illuminate\Support\Facades\View::share('globalSettings', $settings);
        }
    }
}
