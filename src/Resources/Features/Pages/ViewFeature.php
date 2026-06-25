<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Features\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrmFilament\RelationManagers\FeatureVotersRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Features\FeatureResource;
use VentureDrake\LaravelCrmFilament\Widgets\FeatureActivityStatsWidget;
use VentureDrake\LaravelCrmFilament\Widgets\FeatureViewsChart;
use VentureDrake\LaravelCrmFilament\Widgets\FeatureVotesChart;

class ViewFeature extends ViewRecord
{
    protected static string $resource = FeatureResource::class;

    public function getTitle(): string | Htmlable
    {
        return (string) ($this->record?->title ?? $this->record?->getKey());
    }

    public function getSubheading(): string | Htmlable | null
    {
        $record = $this->record;

        if (! $record instanceof Feature) {
            return null;
        }

        $status = $record->status;
        $hex = $status?->color;
        $statusName = $status?->name;
        $isPublic = (bool) $record->is_public;

        if (! $statusName && $isPublic) {
            return null;
        }

        $pills = [];

        if ($statusName) {
            $bg = $hex ? '#' . ltrim($hex, '#') : '#6b7280';
            $pills[] = sprintf(
                '<span style="background-color: %s; color: #ffffff; padding: 2px 8px; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">%s</span>',
                e($bg),
                e($statusName),
            );
        }

        if (! $isPublic) {
            $pills[] = sprintf(
                '<span style="background-color: #6b7280; color: #ffffff; padding: 2px 8px; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">%s</span>',
                e(__('laravel-crm-filament::labels.misc.private')),
            );
        }

        return new HtmlString(implode(' ', $pills));
    }

    protected function getHeaderActions(): array
    {
        return [
            FeatureResource::backToIndexAction(),
            Actions\Action::make('publicView')
                ->label(__('laravel-crm-filament::labels.actions.public_view'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->visible(fn (Feature $r) => $r->is_public)
                ->openUrlInNewTab()
                ->url(fn (Feature $r) => Route::has('laravel-crm.portal.features.show')
                    ? route('laravel-crm.portal.features.show', $r->external_id)
                    : '#'),
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
        $record = $this->getRecord();
        $key = $record->getKey();

        $ownerData = [
            'ownerRecord' => $record,
            'pageClass' => static::class,
        ];

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                $this->getInfolistContentComponent()->columnSpan(['lg' => 1]),
                Livewire::make(FeatureActivityStatsWidget::class, ['record' => $record])
                    ->key('feature-activity-' . $key)
                    ->columnSpan(['lg' => 1]),
            ]),

            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Livewire::make(FeatureVotesChart::class, ['record' => $record])
                    ->key('feature-votes-chart-' . $key)
                    ->columnSpan(['lg' => 1]),
                Livewire::make(FeatureViewsChart::class, ['record' => $record])
                    ->key('feature-views-chart-' . $key)
                    ->columnSpan(['lg' => 1]),
            ]),

            Livewire::make(FeatureVotersRelationManager::class, $ownerData)
                ->key('feature-voters-' . $key)
                ->columnSpanFull(),
        ]);
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }
}
