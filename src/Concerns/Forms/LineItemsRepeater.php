<?php

namespace VentureDrake\LaravelCrmFilament\Concerns\Forms;

use Filament\Forms;
use VentureDrake\LaravelCrm\Models\Product;

/**
 * Shared products line-items Repeater used by Deal, Quote, Order, Invoice,
 * and PurchaseOrder create/edit forms.
 *
 * Each entity stores its line items in a sibling table whose foreign-key
 * column name differs (`deal_product_id`, `quote_product_id`,
 * `order_product_id`, `invoice_line_id`, `purchase_order_line_id`). Pass
 * the FK column name as `$fkColumn` so the repeater renders the correct
 * hidden field per row — necessary so Edit hooks can match existing rows
 * back to their primary key.
 *
 * Row shape (matches the *Service::create/update array contract):
 *   id (product_id), price, quantity, amount (read-only)
 *
 * The row also exposes `comments` as an optional fifth field.
 */
class LineItemsRepeater
{
    public static function products(string $fkColumn = 'deal_product_id'): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('products')
            ->label(__('laravel-crm-filament::labels.money.line_items'))
            ->schema([
                Forms\Components\Hidden::make($fkColumn),
                Forms\Components\Select::make('id')
                    ->label(__('laravel-crm-filament::labels.money.product'))
                    ->options(fn () => Product::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $product = Product::find($state);
                        if ($product && method_exists($product, 'getDefaultPrice')) {
                            $price = $product->getDefaultPrice();
                            $set('price', $price ? $price->price / 100 : 0);
                        }
                    }),
                Forms\Components\TextInput::make('price')
                    ->label(__('laravel-crm-filament::labels.money.unit_price'))
                    ->numeric()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $set('amount', (float) $state * (float) $get('quantity'));
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $set('amount', (float) $state * (float) $get('price'));
                    }),
                Forms\Components\TextInput::make('amount')
                    ->label(__('laravel-crm-filament::labels.money.amount'))
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('comments')
                    ->maxLength(255),
            ])
            ->columns(5)
            ->addActionLabel(__('laravel-crm-filament::labels.actions.add_line_item'))
            ->defaultItems(0)
            ->reorderable()
            ->columnSpanFull();
    }
}
