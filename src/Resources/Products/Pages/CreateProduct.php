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

        $record = app(ProductService::class)->create(FormPayload::wrap($data));
        ProductResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
