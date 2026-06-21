<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Leads\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LeadResource::backToIndexAction()
                ->hiddenLabel(),
            LeadResource::convertAction()
                ->label(__('laravel-crm-filament::labels.actions.convert')),
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

    public function getSubheading(): string | Htmlable | null
    {
        $stage = $this->record?->pipelineStage?->name;

        if (! $stage) {
            return null;
        }

        return new HtmlString(
            '<span class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1 text-sm font-medium text-white dark:bg-gray-100 dark:text-gray-900">'
            . e($stage)
            . '</span>'
        );
    }
}
