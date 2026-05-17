<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\ProductService;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ProductService::class)->create(FormPayload::wrap($data));
    }
}
