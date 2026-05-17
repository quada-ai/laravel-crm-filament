<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\QuoteService;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = app(QuoteService::class)->create(FormPayload::wrap($data));
        QuoteResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
