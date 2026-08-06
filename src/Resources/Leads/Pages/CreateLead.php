<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Leads\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\LeadService;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $pipeline = \VentureDrake\LaravelCrmFilament\Support\DefaultPipeline::ensureFor(\VentureDrake\LaravelCrm\Models\Lead::class);
        \VentureDrake\LaravelCrmFilament\Support\DefaultFieldGroup::ensureFor(\VentureDrake\LaravelCrm\Models\Lead::class);
        if (empty($data['pipeline_id'])) {
            $data['pipeline_id'] = $pipeline->id;
        }
        if (empty($data['pipeline_stage_id'])) {
            $stage = $pipeline->pipelineStages()->orderBy('order')->first();
            $data['pipeline_stage_id'] = $stage?->id;
        }

        if (empty($data['currency'])) {
            $data['currency'] = app('laravel-crm.settings')->get('currency')
                ?: config('laravel-crm.default_currency', 'USD');
        }

        $person = isset($data['person_id']) ? Person::find($data['person_id']) : null;
        $organization = isset($data['organization_id']) ? Organization::find($data['organization_id']) : null;

        $record = app(LeadService::class)->create(FormPayload::wrap($data), $person, $organization);
        if (array_key_exists('labels', $data)) {
            $record->labels()->sync($data['labels'] ?? []);
        }
        LeadResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
