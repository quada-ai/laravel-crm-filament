<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use VentureDrake\LaravelCrm\Mail\SendQuote;
use VentureDrake\LaravelCrm\Models\Quote;

trait HasQuoteSendAction
{
    protected function quoteSendAction(): Action
    {
        return Action::make('send')
            ->label('Send quote')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->modalHeading('Send quote')
            ->modalSubmitActionLabel('Send')
            ->schema(fn (Quote $record): array => [
                TextInput::make('to')
                    ->label('To')
                    ->email()
                    ->required()
                    ->default(fn () => optional($record->person)->getPrimaryEmail()?->address),
                TextInput::make('subject')
                    ->required()
                    ->default(fn () => 'Quote '.$record->quote_id),
                Textarea::make('message')
                    ->rows(8)
                    ->default("Hi,\n\nPlease find your quote here: [Online Quote Link]\n\nThanks."),
                Checkbox::make('cc')
                    ->label('Send me a copy'),
            ])
            ->action(function (array $data, Quote $record): void {
                $this->dispatchQuote($record, $data);

                Notification::make()
                    ->title('Quote sent')
                    ->success()
                    ->send();
            });
    }

    protected function dispatchQuote(Quote $record, array $data): void
    {
        $signedUrl = URL::temporarySignedRoute(
            'laravel-crm.portal.quotes.show',
            now()->addDays(14),
            ['quote' => $record],
        );

        $pdfPath = $this->generateQuotePdf($record);

        Mail::send(new SendQuote([
            'to' => $data['to'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'cc' => ! empty($data['cc']) ? 1 : 0,
            'onlineQuoteLink' => $signedUrl,
            'pdf' => $pdfPath,
        ]));
    }

    protected function generateQuotePdf(Quote $record): string
    {
        $relativeDir = 'laravel-crm/quote/'.$record->id;
        Storage::makeDirectory($relativeDir);

        $pdfRelative = 'app/'.$relativeDir.'/quote-'.strtolower((string) $record->quote_id).'.pdf';

        $settings = app('laravel-crm.settings');

        Pdf::setOption(['fontDir' => public_path('vendor/laravel-crm/fonts')])
            ->loadView('laravel-crm::quotes.pdf', [
                'quote' => $record,
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
