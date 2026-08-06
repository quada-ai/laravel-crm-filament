<?php

namespace VentureDrake\LaravelCrmFilament\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Support\DefaultFieldGroup;
use VentureDrake\LaravelCrmFilament\Support\DefaultPipeline;

class CheckCrmDbCommand extends Command
{
    protected $signature = 'crm:check-db';

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

            $count = Pipeline::count();
            $this->line("   Total pipelines: {$count}");

            foreach (Pipeline::all() as $p) {
                $model = $p->model ?? 'null';
                $isDefault = isset($p->default) ? ($p->default ? 'YES' : 'NO') : 'N/A';
                $this->line("   - ID {$p->id}: Name='{$p->name}', Model='{$model}', Default={$isDefault}");
            }
        }
        $this->newLine();

        // 2. Pipeline Stages
        $this->comment('2. Checking crm_pipeline_stages table...');
        $stageTable = (new PipelineStage)->getTable();
        if (! Schema::hasTable($stageTable)) {
            $this->error("Table {$stageTable} does NOT exist!");
        } else {
            $count = PipelineStage::count();
            $this->line("   Total pipeline stages: {$count}");
            foreach (PipelineStage::all() as $s) {
                $this->line("   - ID {$s->id}: Name='{$s->name}', PipelineID={$s->pipeline_id}, Order={$s->order}");
            }
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

            $count = FieldGroup::count();
            $this->line("   Total field groups: {$count}");
            foreach (FieldGroup::all() as $fg) {
                $model = $fg->model ?? 'null';
                $isDefault = isset($fg->default) ? ($fg->default ? 'YES' : 'NO') : 'N/A';
                $this->line("   - ID {$fg->id}: Name='{$fg->name}', Model='{$model}', Default={$isDefault}");
            }
        }
        $this->newLine();

        // 4. Test Auto-Provisioning
        $this->comment('4. Testing Auto-Provisioning...');
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

        // 5. Test Creating a Deal in DB Transaction
        $this->comment('5. Testing Deal creation dry-run...');
        DB::beginTransaction();
        try {
            $pipeline = DefaultPipeline::ensureFor(Deal::class);
            $stage = $pipeline->pipelineStages()->orderBy('order')->first();

            $data = [
                'title' => 'Diagnostic Test Deal',
                'pipeline_id' => $pipeline->id,
                'pipeline_stage_id' => $stage?->id,
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
