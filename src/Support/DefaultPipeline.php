<?php

namespace VentureDrake\LaravelCrmFilament\Support;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;

class DefaultPipeline
{
    public static function ensureFor(string $modelClass): Pipeline
    {
        $table = (new Pipeline)->getTable();
        $hasModelCol = Schema::hasColumn($table, 'model');
        $hasDefaultCol = Schema::hasColumn($table, 'default');

        $query = Pipeline::query();

        if ($hasModelCol && $hasDefaultCol) {
            $pipeline = (clone $query)->where('model', $modelClass)->where('default', 1)->first()
                ?? (clone $query)->where('model', $modelClass)->first()
                ?? (clone $query)->whereNull('model')->where('default', 1)->first()
                ?? (clone $query)->whereNull('model')->first()
                ?? (clone $query)->first();
        } elseif ($hasModelCol) {
            $pipeline = (clone $query)->where('model', $modelClass)->first()
                ?? (clone $query)->whereNull('model')->first()
                ?? (clone $query)->first();
        } elseif ($hasDefaultCol) {
            $pipeline = (clone $query)->where('default', 1)->first()
                ?? (clone $query)->first();
        } else {
            $pipeline = (clone $query)->first();
        }

        if (! $pipeline) {
            $data = [
                'external_id' => (string) Str::uuid(),
                'name' => 'Sales Pipeline',
            ];
            if ($hasModelCol) {
                $data['model'] = $modelClass;
            }
            if ($hasDefaultCol) {
                $data['default'] = 1;
            }

            $pipeline = Pipeline::create($data);

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
        } elseif ($hasDefaultCol && ! $pipeline->default) {
            $pipeline->update(['default' => 1]);
        }

        return $pipeline;
    }
}
