<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Support\CrmTab;

class ListEmailCampaigns extends ListRecords
{
    protected static string $resource = EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        $statusTab = fn (string $label, string $status, ?string $color = null) => CrmTab::make($label, $this)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status))
            ->badge(fn () => EmailCampaign::query()->where('status', $status)->count() ?: null)
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
