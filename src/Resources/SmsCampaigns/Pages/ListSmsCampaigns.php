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
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status))
            ->badge(fn () => SmsCampaign::query()->where('status', $status)->count() ?: null)
            ->badgeColor($color);

        return [
            'all' => CrmTab::make(__('laravel-crm-filament::labels.misc.all'), $this),
            'draft' => $statusTab(__('laravel-crm-filament::labels.campaign.status_draft'), 'draft'),
            'scheduled' => $statusTab(__('laravel-crm-filament::labels.campaign.status_scheduled'), 'scheduled', 'warning'),
            'sending' => $statusTab(__('laravel-crm-filament::labels.campaign.status_sending'), 'sending', 'info'),
            'sent' => $statusTab(__('laravel-crm-filament::labels.campaign.status_sent'), 'sent', 'success'),
            'failed' => $statusTab(__('laravel-crm-filament::labels.campaign.status_failed'), 'failed', 'danger'),
        ];
    }
}
