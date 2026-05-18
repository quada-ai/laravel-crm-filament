<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Adds an inline CSV export bulk action — no Filament queued-export tables required.
 *
 * Resources opt in by passing the slug + filename + column map to
 * ExportsCsv::action(); the returned BulkAction streams a CSV download of the
 * selected rows directly to the browser via PHP's output buffer.
 *
 *   ExportsCsv::action(
 *       label: 'Export CSV',
 *       columns: [
 *           'ID' => fn ($r) => $r->lead_id,
 *           'Title' => fn ($r) => $r->title,
 *           'Amount' => fn ($r) => ($r->amount ?? 0) / 100,
 *       ],
 *       filename: 'leads',
 *   )
 */
class ExportsCsv
{
    /**
     * @param  array<string, \Closure(mixed): mixed>  $columns
     */
    public static function action(array $columns, string $filename = 'export', string $label = 'Export CSV'): BulkAction
    {
        return BulkAction::make('exportCsv')
            ->label($label)
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($columns, $filename): StreamedResponse {
                $stamp = now()->format('Ymd-His');
                $name = $filename.'-'.$stamp.'.csv';

                $response = new StreamedResponse(function () use ($records, $columns): void {
                    $out = fopen('php://output', 'w');

                    // BOM for Excel UTF-8 compatibility.
                    fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));

                    fputcsv($out, array_keys($columns));

                    foreach ($records as $record) {
                        $row = [];
                        foreach ($columns as $extractor) {
                            $value = $extractor($record);
                            if ($value instanceof \DateTimeInterface) {
                                $value = $value->format('Y-m-d H:i:s');
                            }
                            $row[] = is_scalar($value) || $value === null ? $value : (string) $value;
                        }
                        fputcsv($out, $row);
                    }

                    fclose($out);
                });

                $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
                $response->headers->set('Content-Disposition', "attachment; filename=\"{$name}\"");

                return $response;
            });
    }
}
