<?php

use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ListOrganizations;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\ListProducts;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User as TestUser;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Setting::query()->firstOrCreate(['name' => 'currency'], [
        'external_id' => Str::uuid()->toString(),
        'value' => 'USD',
    ]);
    RoleSeeder::seed();
    $user = TestUser::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('p')]);
    $user->assignRole('Admin');
    $this->actingAs($user);
});

it('hides the Xero column on Organization list when xero module is disabled', function () {
    /** @var ListOrganizations $page */
    $page = livewire(ListOrganizations::class)->instance();
    $col = $page->getTable()->getColumns()['xero_contact'];

    expect($col)->toBeInstanceOf(IconColumn::class);
    expect($col->isVisible())->toBeFalse();
});

it('hides the Xero column on Product list when xero module is disabled', function () {
    /** @var ListProducts $page */
    $page = livewire(ListProducts::class)->instance();
    $col = $page->getTable()->getColumns()['xero_contact_indicator'];

    expect($col)->toBeInstanceOf(IconColumn::class);
    expect($col->isVisible())->toBeFalse();
});

it('source contains the visible() gate referencing isModuleEnabled xero', function (string $sourcePath) {
    $src = file_get_contents($sourcePath);
    expect($src)->toContain("LaravelCrmPlugin::get()->isModuleEnabled('xero')");
})->with([
    'OrganizationResource' => [
        (new ReflectionClass(OrganizationResource::class))->getFileName(),
    ],
    'ProductResource' => [
        (new ReflectionClass(ProductResource::class))->getFileName(),
    ],
]);
