<?php

use Illuminate\Database\Migrations\Migration;
use VentureDrake\LaravelCrmFilament\Tests\TestSchema;

return new class extends Migration
{
    public function up(): void
    {
        TestSchema::up();
    }

    public function down(): void
    {
        // No-op: the test database is dropped between test runs.
    }
};
