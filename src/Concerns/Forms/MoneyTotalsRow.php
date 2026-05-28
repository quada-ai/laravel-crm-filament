<?php

namespace VentureDrake\LaravelCrmFilament\Concerns\Forms;

use Filament\Forms;
use Filament\Schemas\Components\Grid;

/**
 * The 5-rollup money totals shared by Quote, Order, Invoice, and
 * PurchaseOrder forms — sub_total, discount, tax, adjustment, total.
 *
 * Renders as a 2-col outer Grid: left half empty, right half is a 1-col
 * vertical stack of 5 inlineLabel TextInputs (matching core CRM's tfoot
 * which stacks totals to the right of the line-item table). Collapses to
 * a single column on screens < md.
 *
 * Form keys match the core CRM Livewire form keys:
 *   sub_total  -> service writes `subtotal` column ×100
 *   discount   -> service writes `discount` column ×100
 *   tax        -> service writes `tax` column ×100
 *   adjustment -> service writes `adjustments` column ×100
 *   total      -> service writes `total` column ×100 (read-only on form)
 */
class MoneyTotalsRow
{
    public static function make(): Grid
    {
        return Grid::make(['default' => 1, 'md' => 2])->schema([
            Grid::make(1)->schema([]),
            Grid::make(1)->schema([
                Forms\Components\TextInput::make('sub_total')
                    ->label(__('laravel-crm-filament::labels.money.subtotal'))
                    ->numeric()
                    ->inlineLabel(),
                Forms\Components\TextInput::make('discount')
                    ->label(__('laravel-crm-filament::labels.money.discount'))
                    ->numeric()
                    ->inlineLabel(),
                Forms\Components\TextInput::make('tax')
                    ->label(__('laravel-crm-filament::labels.money.tax'))
                    ->numeric()
                    ->inlineLabel(),
                Forms\Components\TextInput::make('adjustment')
                    ->label(__('laravel-crm-filament::labels.money.adjustment'))
                    ->numeric()
                    ->inlineLabel(),
                Forms\Components\TextInput::make('total')
                    ->label(__('laravel-crm-filament::labels.money.total'))
                    ->numeric()
                    ->inlineLabel()
                    ->readOnly(),
            ]),
        ]);
    }
}
