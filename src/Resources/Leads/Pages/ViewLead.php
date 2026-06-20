<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Leads\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LeadResource::backToIndexAction(),
            LeadResource::convertAction(),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                $this->getInfolistContentComponent()->columnSpan(['lg' => 2]),
                $this->getRelationManagersContentComponent()->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
