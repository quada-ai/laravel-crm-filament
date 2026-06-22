<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\RelationManagers\RelatedOrganizationsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\RelatedPeopleRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()->color('gray')];
    }

    public function content(Schema $schema): Schema
    {
        $ownerData = [
            'ownerRecord' => $this->getRecord(),
            'pageClass' => static::class,
        ];

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Group::make([
                    $this->getInfolistContentComponent(),
                    Livewire::make(RelatedPeopleRelationManager::class, $ownerData)
                        ->key('related-people-' . $this->getRecord()->getKey()),
                    Livewire::make(RelatedOrganizationsRelationManager::class, $ownerData)
                        ->key('related-organizations-' . $this->getRecord()->getKey()),
                ])->columnSpan(['lg' => 1]),
                $this->getRelationManagersContentComponent()->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
