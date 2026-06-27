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

        $statusName = $record->status?->name;
        $isPublic = (bool) $record->is_public;

        if (! $statusName && $isPublic) {
            return null;
        }

        // Mirror the Lead show-page subheading shape: a single dark pill per
        // info chip, so Feature reads visually alongside Lead / Deal / Quote
        // rather than introducing a per-status colored pill of its own.
        $pillClass = 'inline-flex items-center rounded-md bg-gray-900 px-3 py-1 text-sm font-medium text-white dark:bg-gray-100 dark:text-gray-900';

        $pills = [];

        if ($statusName) {
            $pills[] = '<span class="' . $pillClass . '">' . e($statusName) . '</span>';
        }

        if (! $isPublic) {
            $pills[] = '<span class="' . $pillClass . '">'
                . e(__('laravel-crm-filament::labels.misc.private'))
                . '</span>';
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
