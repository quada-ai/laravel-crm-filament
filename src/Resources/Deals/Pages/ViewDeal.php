<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealMarkLostAction;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealMarkWonAction;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealReopenAction;

class ViewDeal extends ViewRecord
{
    use HasDealMarkLostAction;
    use HasDealMarkWonAction;
    use HasDealReopenAction;

    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DealResource::backToIndexAction(),
            $this->dealMarkWonAction(),
            $this->dealMarkLostAction(),
            $this->dealReopenAction(),
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
