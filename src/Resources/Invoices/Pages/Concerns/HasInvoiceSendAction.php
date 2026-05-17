<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use VentureDrake\LaravelCrm\Mail\SendInvoice;
use VentureDrake\LaravelCrm\Models\Invoice;

trait HasInvoiceSendAction
{
    protected function invoiceSendAction(): Action
    {
        return Action::make('send')
            ->label('Send invoice')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->modalHeading('Send invoice')
            ->modalSubmitActionLabel('Send')
            ->schema(fn (Invoice $record): array => [
                TextInput::make('to')
                    ->label('To')
                    ->email()
                    ->required()
                    ->default(fn () => optional($record->person)->getPrimaryEmail()?->address),
                TextInput::make('subject')
                    ->required()
                    ->default(fn () => 'Invoice '.$record->invoice_id),
                Textarea::make('message')
                    ->rows(8)
                    ->default("Hi,\n\nPlease find your invoice here: [Online Invoice Link]\n\nThanks."),
                Checkbox::make('cc')
                    ->label('Send me a copy'),
            ])
            ->action(function (array $data, Invoice $record): void {
                $this->dispatchInvoice($record, $data);

                Notification::make()
                    ->title('Invoice sent')
                    ->success()
                    ->send();
            });
    }

    protected function dispatchInvoice(Invoice $record, array $data): void
    {
        $signedUrl = URL::temporarySignedRoute(
            'laravel-crm.portal.invoices.show',
            now()->addDays(14),
            ['invoice' => $record],
        );

        $pdfPath = $this->generateInvoicePdf($record);

        Mail::send(new SendInvoice([
            'to' => $data['to'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'cc' => ! empty($data['cc']) ? 1 : 0,
            'onlineInvoiceLink' => $signedUrl,
            'pdf' => $pdfPath,
        ]));
    }

    protected function generateInvoicePdf(Invoice $record): string
    {
        $relativeDir = 'laravel-crm/invoice/'.$record->id;
        Storage::makeDirectory($relativeDir);

        $pdfRelative = 'app/'.$relativeDir.'/invoice-'.strtolower((string) $record->invoice_id).'.pdf';

        $settings = app('laravel-crm.settings');

        Pdf::setOption(['fontDir' => public_path('vendor/laravel-crm/fonts')])
            ->loadView('laravel-crm::invoices.pdf', [
                'invoice' => $record,
                'dateFormat' => $settings->get('date_format', config('laravel-crm.date_format')),
                'email' => optional($record->person)->getPrimaryEmail(),
                'phone' => optional($record->person)->getPrimaryPhone(),
                'address' => optional($record->person)->getPrimaryAddress(),
                'organization_address' => optional($record->organization)->getPrimaryAddress(),
                'fromName' => $settings->get('organization_name'),
                'logo' => $settings->get('logo_file'),
            ])
            ->save(storage_path($pdfRelative));

        return $pdfRelative;
    }
}
