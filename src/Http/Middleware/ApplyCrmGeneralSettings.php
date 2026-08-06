<?php

namespace VentureDrake\LaravelCrmFilament\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyCrmGeneralSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('laravel-crm.settings')) {
            /** @var \VentureDrake\LaravelCrm\Services\SettingService $settings */
            $settings = app('laravel-crm.settings');

            // 1. Currency setting
            $currency = $settings->get('currency');
            if ($currency) {
                config(['laravel-crm.default_currency' => $currency]);
            }

            // 2. Language / Localisation setting
            $language = $settings->get('language');
            if ($language) {
                app()->setLocale($language);
            }

            // 3. Timezone setting
            $timezone = $settings->get('timezone');
            if ($timezone) {
                date_default_timezone_set($timezone);
                config(['app.timezone' => $timezone]);
            }
        }

        return $next($request);
    }
}
