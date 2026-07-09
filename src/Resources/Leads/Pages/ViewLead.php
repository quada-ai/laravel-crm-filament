<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Leads\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LeadResource::backToIndexAction(),
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
