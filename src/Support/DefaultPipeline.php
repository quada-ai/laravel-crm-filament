<?php

namespace VentureDrake\LaravelCrmFilament\Support;

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;

class DefaultPipeline
{
    public static function ensureFor(string $modelClass): Pipeline
    {
        $pipeline = Pipeline::where('model', $modelClass)->where('default', 1)->first()
            ?? Pipeline::where('model', $modelClass)->first()
            ?? Pipeline::whereNull('model')->where('default', 1)->first()
            ?? Pipeline::whereNull('model')->first()
            ?? Pipeline::first();

        if (! $pipeline) {
            $pipeline = Pipeline::create([
                'external_id' => (string) Str::uuid(),
                'name' => 'Sales Pipeline',
                'model' => $modelClass,
                'default' => 1,
            ]);

            $stages = [
                ['name' => 'Prospect', 'order' => 1],
                ['name' => 'Qualified', 'order' => 2],
                ['name' => 'Proposal Sent', 'order' => 3],
                ['name' => 'Won', 'order' => 4],
                ['name' => 'Lost', 'order' => 5],
            ];

            foreach ($stages as $stage) {
                PipelineStage::create([
                    'external_id' => (string) Str::uuid(),
                    'pipeline_id' => $pipeline->id,
                    'name' => $stage['name'],
                    'order' => $stage['order'],
                ]);
            }
        } elseif (! $pipeline->default) {
            $pipeline->update(['default' => 1]);
        }

        return $pipeline;
    }
}
