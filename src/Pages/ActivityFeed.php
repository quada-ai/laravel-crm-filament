<?php

namespace VentureDrake\LaravelCrmFilament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;

/**
 * Aggregated activity feed replacing the six per-type sidebar entries
 * (Activities/Notes/Calls/Meetings/Lunches/Files) with a single tabbed
 * page. The scope toggle switches between the current user's activity
 * and everyone's; the tab picker narrows the feed to a single type.
 *
 * Skeleton only for US-002 — data loading, filters, and the Blade view
 * land in later stories in the series.
 */
class ActivityFeed extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $title = 'Activity';

    protected static ?string $slug = 'activities';

    protected string $view = 'laravel-crm-filament::activity-feed';

    #[Url]
    public string $scope = 'mine';

    #[Url]
    public string $tab = 'all';

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Activity';
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }
}
