<?php

use VentureDrake\LaravelCrmFilament\Concerns\Imports\OrganizationImporter;
use VentureDrake\LaravelCrmFilament\Concerns\Imports\PersonImporter;
use VentureDrake\LaravelCrmFilament\Concerns\Imports\ProductImporter;
use VentureDrake\LaravelCrmFilament\Concerns\Imports\UserImporter;
use VentureDrake\LaravelCrmFilament\Concerns\ImportsCsv;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ListOrganizations;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\ListPeople;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\ListProducts;
use VentureDrake\LaravelCrmFilament\Resources\Users\Pages\ListUsers;

/**
 * Structural assertions for the v0.6 CSV import actions.
 *
 * Walks the ListRecords pages via reflection so the test runs without
 * booting a full Filament page lifecycle, mirroring the approach used
 * by PipelineConversionActionsTest.
 */
function listHeaderActions(string $page): array
{
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($page, 'getHeaderActions');
    $method->setAccessible(true);

    return $method->invoke($instance);
}

it('registers the Import CSV action on the four target list pages', function () {
    $pages = [
        ListPeople::class,
        ListOrganizations::class,
        ListProducts::class,
        ListUsers::class,
    ];

    foreach ($pages as $page) {
        $names = array_map(fn ($a) => $a->getName(), listHeaderActions($page));
        expect($names)->toContain('importCsv');
    }
});

it('PersonImporter maps the required columns and dedupes by lowercased email', function () {
    $importer = new PersonImporter;

    expect(array_keys($importer->columns()))
        ->toContain('first_name', 'last_name', 'email', 'phone', 'organization');

    expect($importer->dedupeField())->toBe('email');

    expect($importer->dedupeValue(['email' => '  Foo@Bar.COM  ']))
        ->toBe('foo@bar.com');
});

it('OrganizationImporter maps the required columns and dedupes by lowercased name', function () {
    $importer = new OrganizationImporter;

    expect(array_keys($importer->columns()))
        ->toContain('name', 'vat', 'employees', 'revenue', 'linkedin');

    expect($importer->dedupeField())->toBe('name');

    expect($importer->dedupeValue(['name' => '  ACME Inc  ']))
        ->toBe('acme inc');
});

it('UserImporter maps name/email/role and dedupes by email', function () {
    $importer = new UserImporter;

    expect(array_keys($importer->columns()))
        ->toContain('name', 'email', 'role');

    expect($importer->dedupeField())->toBe('email');
});

it('ProductImporter maps name/code/barcode/unit_price/currency/product_category', function () {
    $importer = new ProductImporter;

    expect(array_keys($importer->columns()))
        ->toContain('name', 'code', 'barcode', 'unit_price', 'currency', 'product_category');
});

it('renders a UTF-8-BOM sample CSV with the importer headers and example row', function () {
    $importer = new PersonImporter;
    $response = ImportsCsv::streamSample($importer);

    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    // UTF-8 BOM
    expect(substr($body, 0, 3))->toBe(chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Header line contains the column labels
    expect($body)->toContain('First name')
        ->toContain('Last name')
        ->toContain('Email');

    // Example row from sampleRow()
    expect($body)->toContain('Jane');

    // Content-Disposition advertises a .csv attachment
    expect($response->headers->get('Content-Disposition'))->toContain('.csv');
});

it('exposes a sample download as a modal footer action on the import action', function () {
    $importer = new PersonImporter;
    $action = ImportsCsv::sampleDownloadAction($importer);

    expect($action->getName())->toBe('downloadSample');
});
