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
    private static ?float $cachedDefaultTaxRate = null;
    private static ?string $cachedDefaultCurrency = null;

    public static function products(string $fkColumn = 'deal_product_id', string $priceField = 'price'): Forms\Components\Repeater
    {
        $row2Fields = [
            Forms\Components\TextInput::make($priceField)
                ->label(__('laravel-crm-filament::labels.money.unit_price'))
                ->numeric()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, $get, $set) use ($priceField) {
                    self::recalcRow($get, $set, $priceField);
                    self::recalcFormTotals($get, $set);
                }),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->default(1)
                ->minValue(0)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, $get, $set) use ($priceField) {
                    self::recalcRow($get, $set, $priceField);
                    self::recalcFormTotals($get, $set);
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
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, $get, $set) use ($priceField) {
                        $product = $state ? Product::with(['productPrices', 'taxRate'])->find($state) : null;
                        $unitPrice = 0.0;
                        if ($product) {
                            // 1. Check currency on the current form first
                            $formCurrency = $get('../../currency') ?? $get('../../../currency') ?? $get('currency');

                            // 2. Fall back to cached setting or config
                            if ($formCurrency) {
                                $currency = $formCurrency;
                            } else {
                                if (self::$cachedDefaultCurrency === null) {
                                    try {
                                        self::$cachedDefaultCurrency = \VentureDrake\LaravelCrm\Models\Setting::query()
                                            ->where('name', 'currency')
                                            ->value('value');
                                    } catch (\Throwable $e) {
                                        self::$cachedDefaultCurrency = null;
                                    }
                                }
                                $currency = self::$cachedDefaultCurrency ?: config('laravel-crm.default_currency', 'USD');
                            }

                            // 3. Match price by currency from eager-loaded relation
                            $price = $product->productPrices
                                ->firstWhere('currency', $currency)
                                ?? $product->productPrices->first();

                            $unitPrice = $price && isset($price->unit_price) ? (float) $price->unit_price / 100 : 0.0;
                            $set($priceField, $unitPrice);

                            if (! $get('quantity')) {
                                $set('quantity', 1);
                            }

                            if (! $get('comments') && ! empty($product->description)) {
                                $set('comments', $product->description);
                            }
                        }
                        self::recalcRow($get, $set, $priceField, $unitPrice, $product);
                        self::recalcFormTotals($get, $set);
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
            ->deleteAction(fn ($action) => $action->after(fn ($get, $set) => self::recalcFormTotals($get, $set)))
            ->reorderAction(fn ($action) => $action->after(fn ($get, $set) => self::recalcFormTotals($get, $set)))
            ->columnSpanFull();
    }

    /**
     * Recompute a single row's `amount` (and `tax_amount` when applicable).
     */
    private static function recalcRow($get, $set, string $priceField, ?float $unitPrice = null, ?Product $product = null): void
    {
        if ($unitPrice === null) {
            $unitPrice = (float) ($get($priceField) ?? 0);
        }
        $rawQty = $get('quantity');
        $quantity = $rawQty !== null && $rawQty !== '' ? (float) $rawQty : 1.0;

        $amount = $unitPrice * $quantity;
        $set('amount', $amount);

        if ($priceField === 'unit_price') {
            $rate = self::resolveTaxRate($product, $product ? null : ($get('id') !== null ? (int) $get('id') : null));
            $set('tax_amount', $amount * ((float) $rate / 100.0));
        }
    }

    /**
     * Aggregate sibling rows up to the form-level `sub_total`, `tax`, and
     * `total` fields. Designed to be called from inside a row field's
     * reactive `afterStateUpdated` closure.
     */
    private static function recalcFormTotals($get, $set): void
    {
        // Resolve products rows array defensively across different relative paths
        $raw = $get('../../products') ?? $get('../products') ?? $get('../../') ?? $get('../') ?? [];
        $rows = isset($raw['products']) && is_array($raw['products']) ? $raw['products'] : (is_array($raw) ? $raw : []);

        $subTotal = 0.0;
        $tax = 0.0;

        foreach ($rows as $row) {
            if (is_array($row)) {
                $unit = (float) ($row['unit_price'] ?? $row['price'] ?? 0);
                $qty = isset($row['quantity']) && $row['quantity'] !== '' ? (float) $row['quantity'] : 1.0;
                $rowAmount = isset($row['amount']) && (float) $row['amount'] > 0 ? (float) $row['amount'] : ($unit * $qty);
                $subTotal += $rowAmount;
                $tax += (float) ($row['tax_amount'] ?? 0);
            }
        }

        $discount = (float) ($get('../../discount') ?? $get('../../../discount') ?? $get('discount') ?? 0);
        $adjustment = (float) ($get('../../adjustment') ?? $get('../../../adjustment') ?? $get('adjustment') ?? 0);
        $total = max(0, $subTotal - $discount + $tax + $adjustment);

        $set('../../sub_total', $subTotal);
        $set('../../tax', $tax);
        $set('../../total', $total);
        $set('../../amount', $subTotal);
        $set('../../../amount', $subTotal);
    }

    /**
     * Resolve the tax rate to apply to a line item with memory caching to avoid schema reflection.
     */
    private static function resolveTaxRate(?Product $product = null, ?int $productId = null): float
    {
        if ($product === null && $productId !== null) {
            $product = Product::with('taxRate')->find($productId);
        }

        if ($product) {
            $taxRate = $product->taxRate;
            if ($taxRate instanceof \Illuminate\Database\Eloquent\Builder) {
                $taxRate = $taxRate->first();
            }
            if ($taxRate && isset($taxRate->rate)) {
                return (float) $taxRate->rate;
            }

            if ($product->tax_rate) {
                return (float) $product->tax_rate;
            }
        }

        if (self::$cachedDefaultTaxRate !== null) {
            return self::$cachedDefaultTaxRate;
        }

        try {
            $default = TaxRate::where('default', 1)->first();
            if ($default && isset($default->rate)) {
                return self::$cachedDefaultTaxRate = (float) $default->rate;
            }
        } catch (\Throwable $e) {
            // Ignore if column or table not present
        }

        try {
            $settingService = app('laravel-crm.settings');
            if (method_exists($settingService, 'get')) {
                $setting = $settingService->get('tax_rate');
                if (is_numeric($setting)) {
                    return self::$cachedDefaultTaxRate = (float) $setting;
                }
                if ($setting instanceof \Illuminate\Database\Eloquent\Builder) {
                    $setting = $setting->first();
                }
                if (is_object($setting) && isset($setting->value)) {
                    return self::$cachedDefaultTaxRate = (float) $setting->value;
                }
            }
        } catch (\Throwable $e) {
            // Ignore settings lookup failures
        }

        return self::$cachedDefaultTaxRate = 0.0;
    }
}
