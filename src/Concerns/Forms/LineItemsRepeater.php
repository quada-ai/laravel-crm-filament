<?php

namespace VentureDrake\LaravelCrmFilament\Concerns\Forms;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;

/**
 * Shared products line-items Repeater used by Deal, Quote, Order, Invoice,
 * and PurchaseOrder create/edit forms.
 *
 * Row layout (mirroring core CRM's tfoot stacked rows):
 *   row 1 — hidden FK + product Select (full width)
 *   row 2 — nested Grid: unit_price + quantity + tax_amount + amount
 *           (3-col on Deal — no tax_amount)
 *   row 3 — comments Textarea (full width, 2 rows)
 *
 * The hidden foreign-key column name differs per entity (`deal_product_id`,
 * `quote_product_id`, `order_product_id`, `invoice_line_id`,
 * `purchase_order_line_id`). The unit-price form field name also differs
 * across the family — Deal uses `price`; Quote/Order/Invoice/PurchaseOrder
 * use `unit_price`. tax_amount is only added when $priceField === 'unit_price'.
 *
 * Closure callbacks are typed loosely (no Forms\Get / Forms\Set typehints)
 * because the nested Schemas\Components\Grid causes Filament to pass
 * Schemas\Components\Utilities\Get / Set instead of the Forms namespace
 * variants. Filament resolves these by parameter name, so the untyped
 * shape works for both.
 */
class LineItemsRepeater
{
    public static function products(string $fkColumn = 'deal_product_id', string $priceField = 'price'): Forms\Components\Repeater
    {
        $row2Fields = [
            Forms\Components\TextInput::make($priceField)
                ->label(__('laravel-crm-filament::labels.money.unit_price'))
                ->numeric()
                ->live()
                ->afterStateUpdated(function ($state, $get, $set) {
                    $qty = (float) $get('quantity');
                    $tax = (float) ($get('tax_amount') ?? 0);
                    $set('amount', ((float) $state * $qty) + $tax);
                }),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->default(1)
                ->minValue(0)
                ->live()
                ->afterStateUpdated(function ($state, $get, $set) use ($priceField) {
                    $tax = (float) ($get('tax_amount') ?? 0);
                    $set('amount', (float) $state * (float) $get($priceField) + $tax);
                }),
        ];

        if ($priceField === 'unit_price') {
            $row2Fields[] = Forms\Components\TextInput::make('tax_amount')
                ->label(__('laravel-crm-filament::labels.money.tax'))
                ->numeric()
                ->readOnly();
        }

        $row2Fields[] = Forms\Components\TextInput::make('amount')
            ->label(__('laravel-crm-filament::labels.money.amount'))
            ->numeric()
            ->readOnly();

        $row2Cols = $priceField === 'unit_price' ? 4 : 3;

        return Forms\Components\Repeater::make('products')
            ->label(__('laravel-crm-filament::labels.money.line_items'))
            ->schema([
                Forms\Components\Hidden::make($fkColumn),
                Forms\Components\Select::make('id')
                    ->label(__('laravel-crm-filament::labels.money.product'))
                    ->options(fn () => Product::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) use ($priceField) {
                        $product = Product::find($state);
                        if ($product && method_exists($product, 'getDefaultPrice')) {
                            $price = $product->getDefaultPrice();
                            $set($priceField, $price ? $price->price / 100 : 0);
                        }
                    }),
                Grid::make($row2Cols)->schema($row2Fields),
                Forms\Components\Textarea::make('comments')
                    ->rows(2)
                    ->maxLength(255),
            ])
            ->columns(1)
            ->addActionLabel(__('laravel-crm-filament::labels.actions.add_line_item'))
            ->defaultItems(0)
            ->reorderable()
            ->columnSpanFull();
    }

    /**
     * Resolve the tax rate to apply to a line item, mirroring the fallback
     * chain in core CRM's LiveQuoteItems::calculateAmounts() (lines 133-143):
     *   1. $product->taxRate?->rate   (TaxRate relation on Product)
     *   2. $product->tax_rate          (legacy scalar column on Product)
     *   3. TaxRate::where('default', 1)->first()?->rate
     *   4. app('laravel-crm.settings')->get('tax_rate')?->value
     *   5. 0.0
     */
    private static function resolveTaxRate(?int $productId): float
    {
        if ($productId !== null) {
            $product = Product::find($productId);

            if ($product && $product->taxRate) {
                return (float) $product->taxRate->rate;
            }

            if ($product && $product->tax_rate) {
                return (float) $product->tax_rate;
            }
        }

        if ($default = TaxRate::where('default', 1)->first()) {
            return (float) $default->rate;
        }

        if ($setting = app('laravel-crm.settings')->get('tax_rate')) {
            return (float) $setting->value;
        }

        return 0.0;
    }
}
