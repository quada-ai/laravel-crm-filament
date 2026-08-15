<?php

namespace VentureDrake\LaravelCrmFilament\Http\Middleware;

use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
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
            // Respect the session locale set by the language switcher (filament-language-switch).
            // Only fall back to the CRM database setting when no session locale exists.
            if (session()->has('locale')) {
                app()->setLocale(session()->get('locale'));
            } else {
                $language = $settings->get('language');
                if ($language) {
                    app()->setLocale($language);
                }
            }

            // 3. Timezone setting
            $timezone = $settings->get('timezone');
            if ($timezone) {
                date_default_timezone_set($timezone);
                config(['app.timezone' => $timezone]);
            }

            // 4. Date & Time Formats
            $dateFormat = $settings->get('date_format') ?: config('laravel-crm.date_format', 'Y-m-d');
            $timeFormat = $settings->get('time_format') ?: config('laravel-crm.time_format', 'H:i');
            $dateTimeFormat = trim($dateFormat . ' ' . $timeFormat);

            config([
                'laravel-crm.date_format' => $dateFormat,
                'laravel-crm.time_format' => $timeFormat,
            ]);

            DatePicker::configureUsing(fn (DatePicker $component) => $component->displayFormat($dateFormat));
            DateTimePicker::configureUsing(fn (DateTimePicker $component) => $component->displayFormat($dateTimeFormat));

            // 5. Guarantee default Pipeline, Stages, and FieldGroups exist in database (cached per team/tenant)
            $teamId = \VentureDrake\LaravelCrmFilament\Support\DefaultFieldGroup::resolveCurrentTeamId() ?? 'global';
            $cacheKey = 'laravel_crm_defaults_ensured_' . $teamId;

            if (! \Illuminate\Support\Facades\Cache::has($cacheKey)) {
                try {
                    \VentureDrake\LaravelCrmFilament\Support\DefaultPipeline::ensureFor(\VentureDrake\LaravelCrm\Models\Deal::class);
                    \VentureDrake\LaravelCrmFilament\Support\DefaultPipeline::ensureFor(\VentureDrake\LaravelCrm\Models\Lead::class);
                    \VentureDrake\LaravelCrmFilament\Support\DefaultFieldGroup::ensureAll();
                    \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDay());
                } catch (\Throwable $e) {
                    // Ignore if DB not migrated yet
                }
            }
        }

        return $next($request);
    }
}
