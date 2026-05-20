<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Invoice;

class DealsValueStat extends StatsOverviewWidget
{
    protected ?string $heading = 'Financials';

    protected function getStats(): array
    {
        $currency = config('laravel-crm.default_currency', 'USD');

        // Money fields are stored as integers (cents) — divide for display.
        $openDealsValue = (int) Deal::query()->whereNull('closed_at')->sum('amount');
        $monthRevenue = (int) Invoice::query()
            ->whereDate('issue_date', '>=', now()->startOfMonth())
            ->whereDate('issue_date', '<=', now()->endOfMonth())
            ->sum('total');
        $unpaidRevenue = (int) Invoice::query()
            ->whereNull('fully_paid_at')
            ->sum('amount_due');

        return [
            Stat::make('Open deals value', $this->formatMoney($openDealsValue, $currency))
                ->description('Total of open deal amounts')
                ->color('primary'),

            Stat::make('Invoiced this month', $this->formatMoney($monthRevenue, $currency))
                ->description(now()->format('F'))
                ->color('success'),

            Stat::make('Outstanding receivables', $this->formatMoney($unpaidRevenue, $currency))
                ->description('Across all unpaid invoices')
                ->color('warning'),
        ];
    }

    protected function formatMoney(int $cents, string $currency): string
    {
        $amount = $cents / 100;

        return function_exists('money')
            ? (string) money($amount, $currency)
            : number_format($amount, 2) . ' ' . $currency;
    }
}
