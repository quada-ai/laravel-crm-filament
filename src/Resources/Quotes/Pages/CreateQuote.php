<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\QuoteService;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        if (empty($data['currency'])) {
            $data['currency'] = app('laravel-crm.settings')->get('currency')
                ?: config('laravel-crm.default_currency', 'USD');
        }

        $person = isset($data['person_id']) ? Person::find($data['person_id']) : null;
        $organization = isset($data['organization_id']) ? Organization::find($data['organization_id']) : null;

        $record = app(QuoteService::class)->create(FormPayload::wrap($data), $person, $organization);
        if (array_key_exists('labels', $data)) {
            $record->labels()->sync($data['labels'] ?? []);
        }
        QuoteResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
