<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Services\PurchaseOrderService;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

/**
 * Convert-to-PurchaseOrder action on Order view page. Picks supplier price
 * (Product->getDefaultPrice()->cost_per_unit) when available, falling back to
 * the product's default unit_price.
 */
trait HasOrderConvertToPurchaseOrderAction
{
    protected function orderConvertToPurchaseOrderAction(): Action
    {
        return Action::make('convertToPurchaseOrder')
            ->label(__('laravel-crm-filament::labels.actions.convert_to_purchase_order'))
            ->icon('heroicon-o-clipboard-document-list')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('laravel-crm-filament::labels.actions.create_purchase_order_from_order'))
            ->modalDescription(__('laravel-crm-filament::labels.actions.create_purchase_order_from_order_desc'))
            ->action(function (Order $record, PurchaseOrderService $purchaseOrderService): void {
                $payload = $this->buildPurchaseOrderPayloadFromOrder($record);

                $purchaseOrder = $purchaseOrderService->create(
                    FormPayload::wrap($payload),
                    $record->person,
                    $record->organization,
                );

                $url = PurchaseOrderResource::getUrl('view', ['record' => $purchaseOrder]);

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.purchase_order_created', ['id' => $purchaseOrder->purchase_order_id]))
                    ->body(__('laravel-crm-filament::labels.actions.order_converted_to_purchase_order'))
                    ->success()
                    ->actions([
                        \Filament\Actions\Action::make('open')
                            ->label(__('laravel-crm-filament::labels.actions.open_purchase_order'))
                            ->url($url),
                    ])
                    ->send();
            });
    }

    protected function buildPurchaseOrderPayloadFromOrder(Order $record): array
    {
        $products = [];

        foreach ($record->orderProducts as $orderProduct) {
            $unitPrice = $this->resolveSupplierUnitPrice($orderProduct->product_id, $orderProduct->price);
            $quantity = (float) $orderProduct->quantity;
            $amount = $unitPrice * $quantity;

            $products[] = [
                'id' => $orderProduct->product_id,
                'order_product_id' => $orderProduct->id,
                'quantity' => $orderProduct->quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'comments' => $orderProduct->comments,
            ];
        }

        $deliveryAddressId = null;
        if ($record->organization && ($addr = $record->organization->getPrimaryAddress())) {
            $deliveryAddressId = $addr->id;
        } elseif ($record->person && ($addr = $record->person->getPrimaryAddress())) {
            $deliveryAddressId = $addr->id;
        }

        return [
            'order_id' => $record->id,
            'reference' => $record->reference,
            'issue_date' => now(),
            'delivery_date' => now()->addDays(14),
            'currency' => $record->currency,
            'delivery_type' => $deliveryAddressId ? 'deliver' : 'pickup',
            'delivery_address' => $deliveryAddressId,
            'delivery_instructions' => null,
            'terms' => null,
            'user_owner_id' => $record->user_owner_id,
            'products' => $products,
            // Totals deliberately left blank; PurchaseOrderService recomputes
            // them from $products when $request->total is empty.
        ];
    }

    private function resolveSupplierUnitPrice(?int $productId, ?int $orderProductPriceCents): float
    {
        if ($productId && $product = Product::find($productId)) {
            $price = \VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource::getDefaultPriceRecord($product);

            if ($price && $price->cost_per_unit) {
                return $price->cost_per_unit / 100;
            }

            if ($price && $price->unit_price) {
                return $price->unit_price / 100;
            }
        }

        return $orderProductPriceCents !== null ? $orderProductPriceCents / 100 : 0;
    }
}
