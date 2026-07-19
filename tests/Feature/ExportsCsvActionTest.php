<?php

use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use VentureDrake\LaravelCrmFilament\Concerns\ExportsCsv;

/**
 * Structural + streamed-body assertions for the ExportsCsv bulk-action factory.
 *
 * Mirrors the reflection/ob_start pattern already used in
 * CsvImportActionsTest for ImportsCsv::streamSample().
 */
it('returns a Filament BulkAction named exportCsv with sensible defaults', function () {
    $action = ExportsCsv::action(columns: ['ID' => fn ($r) => $r->id]);

    expect($action)->toBeInstanceOf(BulkAction::class);
    expect($action->getName())->toBe('exportCsv');
    expect($action->getLabel())->toBe('Export CSV');
});

it('honours a custom label override', function () {
    $action = ExportsCsv::action(
        columns: ['ID' => fn ($r) => $r->id],
        label: 'Download CSV',
    );

    expect($action->getLabel())->toBe('Download CSV');
});

it('streams a CSV with UTF-8 BOM, header row, and one row per record', function () {
    $records = new Collection([
        (object) ['id' => 1, 'name' => 'Alpha', 'amount' => 100],
        (object) ['id' => 2, 'name' => 'Beta', 'amount' => 250],
    ]);

    $columns = [
        'ID' => fn ($r) => $r->id,
        'Name' => fn ($r) => $r->name,
        'Amount' => fn ($r) => $r->amount,
    ];

    $action = ExportsCsv::action(columns: $columns, filename: 'leads');

    // Invoke the action's registered closure with the fake record collection.
    /** @var StreamedResponse $response */
    $response = ($action->getActionFunction())($records);

    expect($response)->toBeInstanceOf(StreamedResponse::class);

    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    // UTF-8 BOM prefix so Excel recognises the encoding.
    expect(substr($body, 0, 3))->toBe(chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Header row uses column keys in insertion order.
    expect($body)->toContain('ID,Name,Amount');

    // Rows contain scalar extractor output.
    expect($body)->toContain('1,Alpha,100');
    expect($body)->toContain('2,Beta,250');
});

it('serialises DateTimeInterface values via Y-m-d H:i:s', function () {
    $records = new Collection([
        (object) ['created_at' => new DateTimeImmutable('2026-07-19 08:30:00')],
    ]);

    $action = ExportsCsv::action(
        columns: ['Created' => fn ($r) => $r->created_at],
    );

    $response = ($action->getActionFunction())($records);

    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    expect($body)->toContain('2026-07-19 08:30:00');
});

it('coerces non-scalar/non-datetime column values to string via __toString', function () {
    $stringable = new class
    {
        public function __toString(): string
        {
            return 'stringable-value';
        }
    };

    $records = new Collection([
        (object) ['id' => 1, 'value' => $stringable],
    ]);

    $action = ExportsCsv::action(
        columns: [
            'ID' => fn ($r) => $r->id,
            'Value' => fn ($r) => $r->value,
        ],
    );

    $response = ($action->getActionFunction())($records);

    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    expect($body)->toContain('stringable-value');
});

it('advertises a timestamped .csv filename via Content-Disposition', function () {
    $action = ExportsCsv::action(
        columns: ['ID' => fn ($r) => $r->id],
        filename: 'my-export',
    );

    /** @var StreamedResponse $response */
    $response = ($action->getActionFunction())(new Collection);

    $disposition = $response->headers->get('Content-Disposition');
    $contentType = $response->headers->get('Content-Type');

    expect($contentType)->toBe('text/csv; charset=UTF-8');
    expect($disposition)->toContain('my-export-');
    expect($disposition)->toContain('.csv');
});
