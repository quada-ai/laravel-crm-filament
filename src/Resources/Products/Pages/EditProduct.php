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
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Product $product */
        $product = $this->record;

        $defaultPrice = $product->getDefaultPrice();
        if ($defaultPrice) {
            $data['unit_price'] = $defaultPrice->price !== null ? $defaultPrice->price / 100 : null;
            $data['currency'] = $defaultPrice->currency ?: $data['currency'] ?? config('laravel-crm.default_currency', 'USD');
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Product $record */
        app(ProductService::class)->update($record, FormPayload::wrap($data));

        return $record->refresh();
    }
}
