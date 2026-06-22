<?php

namespace VentureDrake\LaravelCrmFilament\Resources\People\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;

class ViewPerson extends ViewRecord
{
    protected static string $resource = PersonResource::class;

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
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                $this->getInfolistContentComponent()->columnSpan(['lg' => 1]),
                $this->getRelationManagersContentComponent()->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
