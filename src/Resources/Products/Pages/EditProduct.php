<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Products\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Services\ProductService;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-eye'),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Product $product */
        $product = $this->record;

        $data['product_category'] = $product->product_category_id;

        // Safely get default price — Product::getDefaultPrice() calls
        // Setting::currency()->value which crashes when no Setting record exists.
        try {
            $currency = config('laravel-crm.default_currency', 'USD');
            $settingModel = \VentureDrake\LaravelCrm\Models\Setting::query()
                ->where('name', 'currency')
                ->first();
            if ($settingModel && $settingModel->value) {
                $currency = $settingModel->value;
            }

            $defaultPrice = $product->productPrices()
                ->where('currency', $currency)
                ->first();
        } catch (\Throwable $e) {
            $defaultPrice = $product->productPrices()->first();
        }

        if ($defaultPrice) {
            $data['unit_price'] = $defaultPrice->unit_price !== null ? $defaultPrice->unit_price / 100 : null;
            $data['currency'] = $defaultPrice->currency ?: $data['currency'] ?? config('laravel-crm.default_currency', 'USD');
        }

        return ProductResource::loadCrmCustomFieldsInto($data, $this->getRecord());
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data = CreateProduct::sanitizeProductData($data);

        /** @var Product $record */
        app(ProductService::class)->update($record, FormPayload::wrap($data));
        ProductResource::saveCrmCustomFields($data, $record);

        return $record->refresh();
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }
}
