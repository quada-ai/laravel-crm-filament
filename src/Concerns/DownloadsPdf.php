<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Shared PDF generation + download trait for Quote / Invoice / PurchaseOrder
 * documents. Send concerns delegate file-on-disk generation here; ViewRecord
 * pages also use it to expose a standalone "Download PDF" header action.
 *
 * Subdirectory convention mirrors the original Send concerns
 * (storage_path('app/laravel-crm/{type}/{id}/{prefix}-{number}.pdf')) so
 * existing mailers keep finding their attachment in the same location.
 */
trait DownloadsPdf
{
    /**
     * Render the given Blade view to disk and return the storage-path-relative
     * location (e.g. `app/laravel-crm/quote/42/quote-q1042.pdf`).
     */
    protected function renderPdfToDisk(
        Model $record,
        string $type,
        string $prefix,
        string $view,
        array $data,
    ): string {
        $relativeDir = 'laravel-crm/' . $type . '/' . $record->id;
        $pdfRelative = 'app/' . $relativeDir . '/' . $this->pdfFilename($record, $type, $prefix);
        $fullPath = storage_path($pdfRelative);

        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($fullPath));

        Pdf::setOption(['fontDir' => public_path('vendor/laravel-crm/fonts')])
            ->loadView($view, $data)
            ->save($fullPath);

        return $pdfRelative;
    }

    /**
     * Stream the generated PDF as an HTTP download response.
     */
    protected function streamPdfDownload(
        Model $record,
        string $type,
        string $prefix,
        string $view,
        array $data,
    ) {
        $pdfRelative = $this->renderPdfToDisk($record, $type, $prefix, $view, $data);

        return Response::download(storage_path($pdfRelative), $this->pdfFilename($record, $type, $prefix));
    }

    /**
     * Build the shared "Download PDF" header action. Each ViewRecord page wires
     * this to the appropriate generator via the $build callback (which should
     * return a streamed Response).
     */
    protected function downloadPdfAction(callable $build): Action
    {
        return Action::make('downloadPdf')
            ->label(__('laravel-crm-filament::labels.actions.download_pdf'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action($build);
    }

    private function pdfFilename(Model $record, string $type, string $prefix): string
    {
        $numberAttr = match ($type) {
            'delivery' => 'delivery_id',
            'order' => 'order_id',
            'quote' => 'quote_id',
            'invoice' => 'invoice_id',
            'purchaseorder' => 'purchase_order_id',
            default => 'id',
        };

        return $prefix . '-' . strtolower((string) ($record->{$numberAttr} ?? $record->external_id)) . '.pdf';
    }
}
