<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Monitors\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrmFilament\Resources\Monitors\MonitorResource;
use VentureDrake\LaravelCrmFilament\Widgets\MonitorResponseTimeChart;
use VentureDrake\LaravelCrmFilament\Widgets\MonitorStatsWidget;

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

    public function getSubheading(): string | Htmlable | null
    {
        /** @var Monitor|null $record */
        $record = $this->record;

        if ($record === null) {
            return null;
        }

        $parts = array_filter([$record->monitor_id, $record->url], fn ($v) => filled($v));

        return $parts === [] ? null : implode(' — ', $parts);
    }

    protected function getHeaderActions(): array
    {
        return [
            MonitorResource::backToIndexAction(),
            MonitorResource::runCheckNowAction(),
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

    protected function getHeaderWidgets(): array
    {
        return [
            MonitorStatsWidget::class,
            MonitorResponseTimeChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): array | int
    {
        return 1;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getInfolistContentComponent(),
            $this->getRelationManagersContentComponent(),
        ]);
    }
}
