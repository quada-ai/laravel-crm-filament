<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\ChartWidget;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;

class SmsCampaignSendsOverTimeChart extends ChartWidget
{
    protected ?string $heading = 'Sends over time';

    protected int | string | array $columnSpan = 'full';

    public ?SmsCampaign $record = null;

    public function getDisplayedRecord(): ?SmsCampaign
    {
        if ($this->record instanceof SmsCampaign) {
            return $this->record;
        }

        return null;
    }

    protected function getData(): array
    {
        $record = $this->getDisplayedRecord();

        if (! $record || ! $record->scheduled_at || ! $record->sent_at) {
            return ['datasets' => [], 'labels' => []];
        }

        $spanMinutes = $record->scheduled_at->diffInMinutes($record->sent_at);

        if ($spanMinutes <= 60) {
            return ['datasets' => [], 'labels' => []];
        }

        $rows = SmsCampaignRecipient::query()
            ->where('sms_campaign_id', $record->id)
            ->whereNotNull('sent_at')
            ->orderBy('sent_at')
            ->get(['sent_at']);

        $buckets = [];
        foreach ($rows as $row) {
            $hourKey = $row->sent_at->format('Y-m-d H:00');
            $buckets[$hourKey] = ($buckets[$hourKey] ?? 0) + 1;
        }

        ksort($buckets);

        return [
            'datasets' => [
                [
                    'label' => 'Sends',
                    'data' => array_values($buckets),
                    'borderColor' => '#6505B3',
                    'backgroundColor' => 'rgba(101,5,179,0.2)',
                    'fill' => true,
                ],
            ],
            'labels' => array_keys($buckets),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
