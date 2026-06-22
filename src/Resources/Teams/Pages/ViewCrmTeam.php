<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Teams\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use VentureDrake\LaravelCrmFilament\Resources\Teams\CrmTeamResource;

class ViewCrmTeam extends ViewRecord
{
    protected static string $resource = CrmTeamResource::class;

    public function getTitle(): string | Htmlable
    {
        return $this->getRecordTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            CrmTeamResource::backToIndexAction(),
            Actions\EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->hiddenLabel()
                ->tooltip('Edit'),
            Actions\DeleteAction::make()
                ->icon('heroicon-m-trash')
                ->hiddenLabel()
                ->tooltip('Delete'),
        ];
    }
}
