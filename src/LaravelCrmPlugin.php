<?php

namespace VentureDrake\LaravelCrmFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;

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

        return (bool) config("laravel-crm.modules.{$module}", false);
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
        // Resources, pages, widgets and clusters are registered in later phases.
        // Phase 0 ships an empty plugin so the host panel boots cleanly.
    }

    public function boot(Panel $panel): void
    {
        //
    }
}

