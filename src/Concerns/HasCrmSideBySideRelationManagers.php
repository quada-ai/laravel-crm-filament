<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Livewire\Attributes\Url;

/**
 * Renders a Show page with the infolist on the left and a custom
 * tabs strip on the right that switches between the resource's
 * relation managers.
 *
 * We build the tabs strip manually (instead of using Filament's
 * getRelationManagersContentComponent()) because Filament's
 * Tabs::make()->livewireProperty('activeRelationManager')->contained(false)
 * component's Livewire tab-switching wire breaks when nested inside
 * a Grid ->columnSpan(). Rolling our own strip via plain wire:click
 * handlers restores the side-by-side layout without losing tab
 * switching.
 */
trait HasCrmSideBySideRelationManagers
{
    #[Url(as: 'tab', except: 0)]
    public int $activeTab = 0;

    public function setActiveTab(int $index): void
    {
        $this->activeTab = $index;
    }

    /**
     * Override on subclasses that need to render additional components
     * (typically Livewire embeds) below the infolist in the left column.
     *
     * @return array<int, mixed>
     */
    protected function getLeftColumnComponents(): array
    {
        return [
            $this->getInfolistContentComponent(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Group::make($this->getLeftColumnComponents())->columnSpan(['lg' => 1]),
                View::make('laravel-crm-filament::crm-view-tabs')->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
