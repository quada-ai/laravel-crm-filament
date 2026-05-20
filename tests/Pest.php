<?php

use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;
use VentureDrake\LaravelCrmFilament\Tests\TestCase;

// Core CRM policies declare `App\User` as a typehint. The host app
// usually has either App\User or App\Models\User; in the testbench we
// have neither, so alias our test stub into both so type checks pass.
if (! class_exists('App\\Models\\User')) {
    class_alias(User::class, 'App\\Models\\User');
}
if (! class_exists('App\\User')) {
    class_alias(User::class, 'App\\User');
}

uses(TestCase::class)->in(__DIR__);
