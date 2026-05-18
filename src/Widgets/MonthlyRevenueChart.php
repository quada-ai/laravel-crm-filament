<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use VentureDrake\LaravelCrm\Models\Invoice;

class MonthlyRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Invoiced revenue — last 6 months';

    protected function getData(): array
    {
        $months = [];
        $totals = [];

        $start = now()->subMonths(5)->startOfMonth();

        for ($i = 0; $i < 6; $i++) {
            $cursor = $start->copy()->addMonths($i);
            $months[] = $cursor->format('M Y');

            $cents = (int) Invoice::query()
                ->whereDate('issue_date', '>=', $cursor->copy()->startOfMonth())
                ->whereDate('issue_date', '<=', $cursor->copy()->endOfMonth())
                ->sum('total');

            $totals[] = $cents / 100;
        }

        return [
            'datasets' => [
                [
                    'label' => config('laravel-crm.default_currency', 'USD'),
                    'data' => $totals,
                    'borderColor' => '#05b3a9',
                    'backgroundColor' => 'rgba(5,179,169,0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
