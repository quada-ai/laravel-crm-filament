<?php

namespace VentureDrake\LaravelCrmFilament\Concerns\Forms;

use Filament\Forms;
use VentureDrake\LaravelCrm\Models\Product;

/**
 * Shared products line-items Repeater used by Deal, Quote, Order, Invoice,
 * and PurchaseOrder create/edit forms.
 *
 * The hidden foreign-key column name differs per entity (`deal_product_id`,
 * `quote_product_id`, `order_product_id`, `invoice_line_id`,
 * `purchase_order_line_id`). The unit-price form field name also differs
 * across the family — Deal uses `price`; Quote/Order/Invoice/PurchaseOrder
 * use `unit_price`. Pass both as constructor args so the repeater's row
 * shape matches the destination service's expected payload.
 *
 * Row shape per entity (matches *Service::create/update):
 *   { $fkColumn (hidden), id (product_id), $priceField, quantity, amount, comments }
 */
class LineItemsRepeater
{
    public static function products(string $fkColumn = 'deal_product_id', string $priceField = 'price'): Forms\Components\Repeater
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
                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($priceField) {
                        $product = Product::find($state);
                        if ($product && method_exists($product, 'getDefaultPrice')) {
                            $price = $product->getDefaultPrice();
                            $set($priceField, $price ? $price->price / 100 : 0);
                        }
                    }),
                Forms\Components\TextInput::make($priceField)
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
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) use ($priceField) {
                        $set('amount', (float) $state * (float) $get($priceField));
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
