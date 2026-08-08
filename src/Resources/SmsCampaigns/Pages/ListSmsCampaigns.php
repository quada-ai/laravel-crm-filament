<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;
use VentureDrake\LaravelCrmFilament\Support\CrmTab;

class ListSmsCampaigns extends ListRecords
{
    protected static string $resource = SmsCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        $statusTab = fn (string $label, string $status, ?string $color = null) => CrmTab::make($label, $this)
            ->modifyQueryUsing(fn (Builder $q) => $q->where('status', $status))
            ->badge(fn () => SmsCampaign::query()->where('status', $status)->count() ?: null)
            ->badgeColor($color);

        return [
            'all' => CrmTab::make('All', $this),
            'draft' => $statusTab('Draft', 'draft'),
            'scheduled' => $statusTab('Scheduled', 'scheduled', 'warning'),
            'sending' => $statusTab('Sending', 'sending', 'info'),
            'sent' => $statusTab('Sent', 'sent', 'success'),
            'failed' => $statusTab('Failed', 'failed', 'danger'),
        ];
    }
}
