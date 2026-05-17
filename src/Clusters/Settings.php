<?php

namespace VentureDrake\LaravelCrmFilament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static ?string $slug = 'settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 90;

    public static function getNavigationLabel(): string
    {
        return 'Settings';
    }
}
