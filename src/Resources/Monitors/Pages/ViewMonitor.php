<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Monitors\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrmFilament\Resources\Monitors\MonitorResource;

class ViewMonitor extends ViewRecord
{
    protected static string $resource = MonitorResource::class;

    public function getTitle(): string | Htmlable
    {
        /** @var Monitor|null $record */
        $record = $this->record;

        if ($record === null) {
            return '';
        }

        return $record->name ?: ($record->host ?: ($record->url ?: (string) $record->getKey()));
    }

    protected function getHeaderActions(): array
    {
        return [
            MonitorResource::backToIndexAction(),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square'),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash'),
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
