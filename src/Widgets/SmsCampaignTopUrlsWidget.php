<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsCampaignClick;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;

class SmsCampaignTopUrlsWidget extends TableWidget
{
    protected static ?string $heading = 'Top URLs';

    protected int | string | array $columnSpan = 'full';

    public ?SmsCampaign $record = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('laravel-crm-filament::labels.campaign.top_urls'))
            ->query(fn (): Builder => $this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('original_url')
                    ->label(__('laravel-crm-filament::labels.campaign.url'))
                    ->limit(80)
                    ->tooltip(fn ($record): ?string => $record->original_url)
                    ->sortable(),
                Tables\Columns\TextColumn::make('clicks_count')
                    ->label(__('laravel-crm-filament::labels.campaign.clicks'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unique_recipients')
                    ->label(__('laravel-crm-filament::labels.campaign.unique_recipients'))
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('clicks_count', 'desc')
            ->paginated([5, 10, 25])
            ->emptyStateHeading('No URL clicks yet');
    }

    protected function getQuery(): Builder
    {
        $clicksTable = (new SmsCampaignClick)->getTable();

        $recipientIds = $this->record
            ? SmsCampaignRecipient::query()
                ->where('sms_campaign_id', $this->record->getKey())
                ->pluck('id')
            : collect();

        return SmsCampaignClick::query()
            ->select('original_url')
            ->selectRaw('COUNT(*) as clicks_count')
            ->selectRaw('COUNT(DISTINCT ' . $clicksTable . '.sms_campaign_recipient_id) as unique_recipients')
            ->whereIn($clicksTable . '.sms_campaign_recipient_id', $recipientIds)
            ->groupBy('original_url');
    }
}
