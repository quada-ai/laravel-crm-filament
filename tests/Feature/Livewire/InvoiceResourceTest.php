<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\ListInvoices;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();
    $this->user = User::create([
        'name' => 'Invoice Tester',
        'email' => 'invoice-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing Invoice rows on the ListInvoices page', function () {
    $invoice = Invoice::create([
        'external_id' => (string) Str::uuid(),
        'invoice_id' => 'INV-1001',
    ]);

    livewire(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice]);
});

it('persists a new Invoice to the database', function () {
    $record = Invoice::create([
        'external_id' => (string) Str::uuid(),
    ]);

    expect(Invoice::query()->whereKey($record->getKey())->exists())->toBeTrue();
});

it('routes Invoice records by external_id', function () {
    expect(InvoiceResource::getRecordRouteKeyName())->toBe('external_id');
});
