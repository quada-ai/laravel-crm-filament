<?php

namespace VentureDrake\LaravelCrmFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\TaskResource;
use VentureDrake\LaravelCrmFilament\Widgets\CrmStatsOverview;
use VentureDrake\LaravelCrmFilament\Widgets\LeadsByStageChart;

class LaravelCrmPlugin implements Plugin
{
    /**
     * Optional module overrides. Null means "fall back to config('laravel-crm.modules')".
     *
     * @var array<string,bool>|null
     */
    protected ?array $modules = null;

    protected ?string $navigationGroup = null;

    protected ?string $brand = null;

    public function getId(): string
    {
        return 'laravel-crm';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * @param  array<string,bool>  $modules
     */
    public function modules(array $modules): static
    {
        $this->modules = $modules;

        return $this;
    }

    public function withChat(bool $enabled = true): static
    {
        $this->modules['chat'] = $enabled;

        return $this;
    }

    public function withEmailMarketing(bool $enabled = true): static
    {
        $this->modules['email-marketing'] = $enabled;

        return $this;
    }

    public function withSmsMarketing(bool $enabled = true): static
    {
        $this->modules['sms-marketing'] = $enabled;

        return $this;
    }

    public function withXero(bool $enabled = true): static
    {
        $this->modules['xero'] = $enabled;

        return $this;
    }

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function brand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function isModuleEnabled(string $module): bool
    {
        if ($this->modules !== null && array_key_exists($module, $this->modules)) {
            return (bool) $this->modules[$module];
        }

        // Core CRM stores enabled modules as a flat array of slugs:
        //   config('laravel-crm.modules') === ['leads', 'deals', ...]
        return in_array($module, (array) config('laravel-crm.modules', []), true);
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function register(Panel $panel): void
    {
        // Contact + activity entities are always available (no module flag in core).
        $resources = [
            PersonResource::class,
            OrganizationResource::class,
            TaskResource::class,
        ];

        // Pipeline / marketing entities are gated by core's `laravel-crm.modules` array.
        if ($this->isModuleEnabled('leads')) {
            $resources[] = LeadResource::class;
        }

        if ($this->isModuleEnabled('deals')) {
            $resources[] = DealResource::class;
        }

        if ($this->isModuleEnabled('quotes')) {
            $resources[] = QuoteResource::class;
        }

        $panel->resources($resources);

        $panel->widgets([
            CrmStatsOverview::class,
            LeadsByStageChart::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}








