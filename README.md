# Laravel CRM — Filament Panel Plugin
Filament v5 panel plugin for venturedrake/laravel-crm. Native Filament UI that runs alongside the legacy /crm MaryUI/Livewire interface, sharing the same database, models, services, and policies.
Status: Phase 0 (scaffold only). Resources, pages, and widgets land in later phases — see plan-filamentPlugin.prompt.md in the core package repo.
## Requirements
- PHP ^8.2
- Laravel ^11.0 | ^12.0 | ^13.0
- Filament ^5.0
- venturedrake/laravel-crm ^2.0
## Installation
    composer require venturedrake/laravel-crm-filament
    php artisan laravelcrm:filament-install
The install command publishes app/Providers/Filament/CrmPanelProvider.php (path /admin) and registers it in bootstrap/providers.php.
Add the FilamentUser contract to App\Models\User so users with CRM access can reach the panel.
## Registering the plugin
The published CrmPanelProvider includes:
    ->plugin(
        LaravelCrmPlugin::make()
            // ->modules([...])
            // ->withChat()
            // ->withEmailMarketing()
            // ->withSmsMarketing()
            // ->withXero()
            // ->navigationGroup('CRM')
    );
## Coexistence
The plugin does not touch the legacy /crm routes shipped by venturedrake/laravel-crm. Both UIs can run side-by-side on the same database.
