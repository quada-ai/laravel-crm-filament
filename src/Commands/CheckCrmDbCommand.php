<?php

namespace VentureDrake\LaravelCrmFilament\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Support\DefaultFieldGroup;
use VentureDrake\LaravelCrmFilament\Support\DefaultPipeline;

class CheckCrmDbCommand extends Command
{
    protected $signature = 'crm:check-db {--fix : Automatically fix orphaned records}';

    protected $description = 'Diagnose CRM database state, pipelines, field groups, and model creation';

    public function handle(): int
    {
        $this->info('=== CRM Database Diagnostic Tool ===');
        $this->newLine();

        // 1. Pipelines
        $this->comment('1. Checking crm_pipelines table...');
        $pipelineTable = (new Pipeline)->getTable();
        if (! Schema::hasTable($pipelineTable)) {
            $this->error("Table {$pipelineTable} does NOT exist!");
        } else {
            $cols = Schema::getColumnListing($pipelineTable);
            $this->line('   Columns: ' . implode(', ', $cols));

            $count = Pipeline::withoutGlobalScopes()->count();
            $this->line("   Total pipelines: {$count}");

            foreach (Pipeline::withoutGlobalScopes()->get() as $p) {
                $model = $p->model ?? 'null';
                $isDefault = isset($p->default) ? ($p->default ? 'YES' : 'NO') : 'N/A';
                $teamId = $p->team_id ?? 'null';
                $this->line("   - ID {$p->id}: Name='{$p->name}', Model='{$model}', Default={$isDefault}, TeamID={$teamId}");
            }
        }
        $this->newLine();

        // 2. Pipeline Stages
        $this->comment('2. Checking crm_pipeline_stages table...');
        $stageTable = (new PipelineStage)->getTable();
        if (! Schema::hasTable($stageTable)) {
            $this->error("Table {$stageTable} does NOT exist!");
        } else {
            $count = PipelineStage::withoutGlobalScopes()->count();
            $this->line("   Total pipeline stages: {$count}");
        }
        $this->newLine();

        // 3. Field Groups
        $this->comment('3. Checking crm_field_groups table...');
        $fgTable = (new FieldGroup)->getTable();
        if (! Schema::hasTable($fgTable)) {
            $this->error("Table {$fgTable} does NOT exist!");
        } else {
            $cols = Schema::getColumnListing($fgTable);
            $this->line('   Columns: ' . implode(', ', $cols));

            $count = FieldGroup::withoutGlobalScopes()->count();
            $this->line("   Total field groups: {$count}");
            foreach (FieldGroup::withoutGlobalScopes()->get() as $fg) {
                $model = $fg->model ?? 'null';
                $system = $fg->system ?? 'N/A';
                $handle = $fg->handle ?? 'null';
                $teamId = $fg->team_id ?? 'null';
                $this->line("   - ID {$fg->id}: Name='{$fg->name}', Handle='{$handle}', System={$system}, TeamID={$teamId}");
            }
        }
        $this->newLine();

        // 4. ** KEY CHECK ** Orphaned FieldModel rows (root cause of HasCrmFields.php:21 crash)
        $this->comment('4. Checking crm_field_models for orphaned records...');
        $fmTable = (new FieldModel)->getTable();
        if (! Schema::hasTable($fmTable)) {
            $this->error("Table {$fmTable} does NOT exist!");
        } else {
            $allFieldModels = FieldModel::withoutGlobalScopes()->with('field')->get();
            $this->line("   Total field_models rows: {$allFieldModels->count()}");

            $orphaned = $allFieldModels->filter(fn ($fm) => $fm->field === null);

            if ($orphaned->isEmpty()) {
                $this->info('   No orphaned field_models found. All OK.');
            } else {
                $this->error("   FOUND {$orphaned->count()} ORPHANED field_models row(s) where field relation is NULL!");
                $this->error('   >>> THIS IS THE ROOT CAUSE of HasCrmFields.php:21 "Attempt to read property default on null" <<<');
                foreach ($orphaned as $fm) {
                    $this->line("   - FieldModel ID {$fm->id}: field_id={$fm->field_id}, model='{$fm->model}', team_id=" . ($fm->team_id ?? 'null'));
                }

                if ($this->option('fix')) {
                    $this->warn('   Deleting orphaned field_models rows...');
                    foreach ($orphaned as $fm) {
                        $fm->forceDelete();
                        $this->line("   Deleted FieldModel ID {$fm->id}");
                    }
                    $this->info('   Orphaned rows cleaned up.');
                } else {
                    $this->newLine();
                    $this->warn('   To automatically fix, re-run with: php artisan crm:check-db --fix');
                }
            }
        }
        $this->newLine();

        // 5. Test Auto-Provisioning
        $this->comment('5. Testing Auto-Provisioning...');
        try {
            $dealPipeline = DefaultPipeline::ensureFor(Deal::class);
            $this->info("   Deal Pipeline resolved: ID {$dealPipeline->id} ('{$dealPipeline->name}')");

            $leadPipeline = DefaultPipeline::ensureFor(Lead::class);
            $this->info("   Lead Pipeline resolved: ID {$leadPipeline->id} ('{$leadPipeline->name}')");

            DefaultFieldGroup::ensureAll();
            $this->info('   DefaultFieldGroup::ensureAll() executed successfully.');
        } catch (\Throwable $e) {
            $this->error('   Auto-Provisioning FAILED with exception:');
            $this->error('   ' . $e->getMessage());
            $this->line($e->getTraceAsString());
        }
        $this->newLine();

        // 6. Test Creating a Deal in DB Transaction
        $this->comment('6. Testing Deal creation dry-run...');
        DB::beginTransaction();
        try {
            $pipeline = DefaultPipeline::ensureFor(Deal::class);
            $stage = $pipeline->pipelineStages()->orderBy('order')->first();

            $data = [
                'title' => 'Diagnostic Test Deal',
                'pipeline_id' => $pipeline->id,
                'pipeline_stage_id' => $stage?->id,
                'currency' => app('laravel-crm.settings')->get('currency') ?: config('laravel-crm.default_currency', 'USD'),
            ];

            if (class_exists(\VentureDrake\LaravelCrm\Services\DealService::class)) {
                $deal = app(\VentureDrake\LaravelCrm\Services\DealService::class)->create(
                    new \Illuminate\Support\Fluent($data)
                );
                $this->info("   SUCCESS! Deal created with ID {$deal->id} (external_id: {$deal->external_id})");
            } else {
                $this->warn('   DealService class not found in vendor.');
            }
        } catch (\Throwable $e) {
            $this->error('   Deal creation FAILED with exception:');
            $this->error('   Class: ' . get_class($e));
            $this->error('   Message: ' . $e->getMessage());
            $this->error('   File: ' . $e->getFile() . ':' . $e->getLine());
            $this->newLine();
            $this->error('   Full Trace:');
            $this->line($e->getTraceAsString());
        } finally {
            DB::rollBack();
            $this->line('   (Transaction rolled back cleanly)');
        }

        $this->newLine();
        $this->info('=== Diagnostic Completed ===');

        return 0;
    }
}
