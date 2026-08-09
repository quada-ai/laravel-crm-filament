<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

/**
 * Convert-to-Invoice action on Order view page. Hidden when an invoice already
 * exists. Copies all order line items into the new invoice and links
 * invoice.order_id.
 */
trait HasOrderConvertToInvoiceAction
{
    protected function orderConvertToInvoiceAction(): Action
    {
        return Action::make('convertToInvoice')
            ->label(__('laravel-crm-filament::labels.actions.convert_to_invoice'))
            ->icon('heroicon-o-receipt-percent')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('laravel-crm-filament::labels.actions.convert_order_to_invoice'))
            ->modalDescription(__('laravel-crm-filament::labels.actions.convert_order_to_invoice_desc'))
            ->visible(fn (Order $record): bool => $record->invoices()->count() === 0)
            ->action(function (Order $record, InvoiceService $invoiceService): void {
                $payload = $this->buildInvoicePayloadFromOrder($record);

                $invoice = $invoiceService->create(
                    FormPayload::wrap($payload),
                    $record->person,
                    $record->organization,
                );

                $url = InvoiceResource::getUrl('view', ['record' => $invoice]);

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.invoice_created', ['id' => $invoice->invoice_id]))
                    ->body(__('laravel-crm-filament::labels.actions.order_converted_to_invoice'))
                    ->success()
                    ->actions([
                        \Filament\Actions\Action::make('open')
                            ->label(__('laravel-crm-filament::labels.actions.open_invoice'))
                            ->url($url),
                    ])
                    ->send();
            });
    }

    protected function buildInvoicePayloadFromOrder(Order $record): array
    {
        $products = [];

        foreach ($record->orderProducts as $orderProduct) {
            $products[] = [
                'id' => $orderProduct->product_id,
                'order_product_id' => $orderProduct->id,
                'quantity' => $orderProduct->quantity,
                'unit_price' => $orderProduct->price !== null ? $orderProduct->price / 100 : 0,
                'amount' => $orderProduct->amount !== null ? $orderProduct->amount / 100 : 0,
                'comments' => $orderProduct->comments,
            ];
        }

        return [
            'order_id' => $record->id,
            'reference' => $record->reference,
            'issue_date' => now(),
            'due_date' => now()->addDays((int) (app('laravel-crm.settings')->get('invoice_due_days', 30))),
            'currency' => $record->currency,
            'terms' => null,
            'sub_total' => $record->subtotal !== null ? $record->subtotal / 100 : null,
            'tax' => $record->tax !== null ? $record->tax / 100 : null,
            'total' => $record->total !== null ? $record->total / 100 : null,
            'user_owner_id' => $record->user_owner_id,
            'products' => $products,
        ];
    }
}
