<?php

namespace VentureDrake\LaravelCrmFilament\Support;

class CrmTab
{
    public static function make(string $label, mixed $livewire = null): mixed
    {
        $tab = null;

        if (class_exists(\Filament\Resources\Pages\ListRecords\Tab::class)) {
            $tab = \Filament\Resources\Pages\ListRecords\Tab::make($label);
        } elseif (class_exists(\Filament\Tables\Tabs\Tab::class)) {
            $tab = \Filament\Tables\Tabs\Tab::make($label);
        } elseif (class_exists(\Filament\Resources\Components\Tab::class)) {
            $tab = \Filament\Resources\Components\Tab::make($label);
        } else {
            $tab = \Filament\Schemas\Components\Tabs\Tab::make($label);
        }

        if ($livewire && method_exists($tab, 'livewire')) {
            try {
                $tab->livewire($livewire);
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        if (method_exists($tab, 'hasContainer') && method_exists($tab, 'container')) {
            try {
                if (! $tab->hasContainer()) {
                    if (class_exists(\Filament\Schemas\Schema::class)) {
                        $container = \Filament\Schemas\Schema::make($livewire);
                        $tab->container($container);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return $tab;
    }
}
