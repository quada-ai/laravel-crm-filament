<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Products\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
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

        $defaultPrice = $product->getDefaultPrice();
        if ($defaultPrice) {
            $data['unit_price'] = $defaultPrice->price !== null ? $defaultPrice->price / 100 : null;
            $data['currency'] = $defaultPrice->currency ?: $data['currency'] ?? config('laravel-crm.default_currency', 'USD');
        }

        return ProductResource::loadCrmCustomFieldsInto($data, $this->getRecord());
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! empty($data['tax_rate_id'])) {
            $taxRate = TaxRate::find($data['tax_rate_id']);
            $data['tax_rate'] = $taxRate?->rate;
        } else {
            $data['tax_rate_id'] = null;
            $data['tax_rate'] = null;
        }

        if (empty($data['product_category'])) {
            $data['product_category'] = null;
        }

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
