<?php

namespace VentureDrake\LaravelCrmFilament\Resources\People\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use VentureDrake\LaravelCrmFilament\RelationManagers\RelatedOrganizationsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\RelatedPeopleRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;

class ViewPerson extends ViewRecord
{
    protected static string $resource = PersonResource::class;

    public function getTitle(): string | Htmlable
    {
        return $this->getRecordTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            PersonResource::backToIndexAction(),
            Actions\EditAction::make()->icon('heroicon-m-pencil-square')->hiddenLabel()->tooltip('Edit'),
            Actions\DeleteAction::make()->icon('heroicon-m-trash')->hiddenLabel()->tooltip('Delete'),
        ];
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
