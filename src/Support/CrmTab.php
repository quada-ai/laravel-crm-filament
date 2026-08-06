<?php

namespace VentureDrake\LaravelCrmFilament\Support;

class CrmTab
{
    public static function make(string $label): mixed
    {
        if (class_exists(\Filament\Resources\Components\Tab::class)) {
            return \Filament\Resources\Components\Tab::make($label);
        }

        if (class_exists(\Filament\Resources\Pages\ListRecords\Tab::class)) {
            return \Filament\Resources\Pages\ListRecords\Tab::make($label);
        }

        return \Filament\Schemas\Components\Tabs\Tab::make($label);
    }
}
