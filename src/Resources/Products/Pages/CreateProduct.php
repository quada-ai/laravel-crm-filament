<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Services\ProductService;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data = self::sanitizeProductData($data);

        $record = app(ProductService::class)->create(FormPayload::wrap($data));
        ProductResource::saveCrmCustomFields($data, $record);

        return $record;
    }

    /**
     * Sanitize product form data to prevent MySQL type errors.
     *
     * Converts empty strings to null for nullable numeric/FK columns,
     * resolves tax_rate from the selected tax_rate_id, and ensures
     * currency always has a valid value.
     */
    public static function sanitizeProductData(array $data): array
    {
        // Nullable fields that must be null (not '') when empty
        $nullableFields = [
            'barcode', 'purchase_account', 'sales_account',
            'unit', 'description', 'code',
        ];

        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === '' || $data[$field] === null)) {
                $data[$field] = null;
            }
        }

        // Tax rate: resolve from selected tax_rate_id or set both to null
        if (! empty($data['tax_rate_id'])) {
            $taxRate = TaxRate::find($data['tax_rate_id']);
            $data['tax_rate'] = $taxRate?->rate ?? null;
        } else {
            $data['tax_rate_id'] = null;
            $data['tax_rate'] = null;
        }

        // Product category: null when empty
        if (empty($data['product_category'])) {
            $data['product_category'] = null;
        }

        // User owner: null when empty
        if (empty($data['user_owner_id'])) {
            $data['user_owner_id'] = null;
        }

        // Currency: always required, fallback to settings or config
        if (empty($data['currency'])) {
            try {
                $setting = \VentureDrake\LaravelCrm\Models\Setting::where('name', 'currency')->first();
                $data['currency'] = (string) ($setting?->value ?: config('laravel-crm.default_currency', 'USD'));
            } catch (\Throwable $e) {
                $data['currency'] = (string) config('laravel-crm.default_currency', 'USD');
            }
        }

        return $data;
    }
}
