<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use VentureDrake\LaravelCrm\Mail\SendPurchaseOrder;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrmFilament\Concerns\DownloadsPdf;

trait HasPurchaseOrderSendAction
{
    use DownloadsPdf;

    protected function purchaseOrderSendAction(): Action
    {
        return Action::make('send')
            ->label(__('laravel-crm-filament::labels.actions.send_purchase_order'))
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->modalHeading(__('laravel-crm-filament::labels.actions.send_purchase_order'))
            ->modalSubmitActionLabel(__('laravel-crm-filament::labels.actions.send'))
            ->schema(fn (PurchaseOrder $record): array => [
                TextInput::make('to')
                    ->label(__('laravel-crm-filament::labels.campaign.to'))
                    ->email()
                    ->required(),
                TextInput::make('subject')
                    ->required()
                    ->default(fn () => 'Purchase Order ' . $record->purchase_order_id),
                Textarea::make('message')
                    ->rows(8)
                    ->default("Hi,\n\nPlease find the purchase order here: [Online Purchase Order Link]\n\nThanks."),
                Checkbox::make('cc')
                    ->label(__('laravel-crm-filament::labels.campaign.send_me_a_copy')),
            ])
            ->action(function (array $data, PurchaseOrder $record): void {
                $this->dispatchPurchaseOrder($record, $data);

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.purchase_order_sent'))
                    ->success()
                    ->send();
            });
    }

    protected function purchaseOrderDownloadPdfAction(): Action
    {
        return $this->downloadPdfAction(
            fn (PurchaseOrder $record) => $this->streamPdfDownload(
                $record,
                'purchaseorder',
                'purchase-order',
                'laravel-crm::purchase-orders.pdf',
                $this->purchaseOrderPdfViewData($record),
            ),
        );
    }

    protected function dispatchPurchaseOrder(PurchaseOrder $record, array $data): void
    {
        $signedUrl = Route::has('laravel-crm.portal.purchase-orders.show')
            ? URL::temporarySignedRoute('laravel-crm.portal.purchase-orders.show', now()->addDays(14), ['purchaseOrder' => $record])
            : url('p/purchase-orders/' . $record->external_id);

        $pdfPath = $this->generatePurchaseOrderPdf($record);

        Mail::send(new SendPurchaseOrder([
            'to' => $data['to'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'cc' => ! empty($data['cc']) ? 1 : 0,
            'onlinePurchaseOrderLink' => $signedUrl,
            'pdf' => $pdfPath,
        ]));
    }

    protected function generatePurchaseOrderPdf(PurchaseOrder $record): string
    {
        return $this->renderPdfToDisk(
            $record,
            'purchaseorder',
            'purchase-order',
            'laravel-crm::purchase-orders.pdf',
            $this->purchaseOrderPdfViewData($record),
        );
    }

    protected function purchaseOrderPdfViewData(PurchaseOrder $record): array
    {
        $settings = app('laravel-crm.settings');

        return [
            'purchaseOrder' => $record,
            'dateFormat' => $settings->get('date_format', config('laravel-crm.date_format')),
            'email' => optional($record->person)->getPrimaryEmail(),
            'phone' => optional($record->person)->getPrimaryPhone(),
            'address' => optional($record->person)->getPrimaryAddress(),
            'organization_address' => optional($record->organization)->getPrimaryAddress(),
            'fromName' => $settings->get('organization_name'),
            'logo' => $settings->get('logo_file'),
            'taxName' => $settings->get('tax_name', 'Tax'),
        ];
    }
}
