<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrmFilament\Concerns\DownloadsPdf;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmSideBySideRelationManagers;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;

class ViewDelivery extends ViewRecord
{
    use Concerns\HasDeliveryPortalAction;
    use DownloadsPdf;
    use HasCrmSideBySideRelationManagers;

    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeliveryResource::backToIndexAction(),
            $this->downloadPdfAction(fn (Delivery $record) => $this->streamPdfDownload(
                $record,
                'delivery',
                'delivery',
                'laravel-crm::deliveries.pdf',
                $this->deliveryPdfViewData($record),
            ))
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-arrow-down-tray'),
            $this->deliveryPortalAction()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray'),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square'),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function deliveryPdfViewData(Delivery $record): array
    {
        $settings = app('laravel-crm.settings');
        $order = $record->order;

        return [
            'delivery' => $record,
            'order' => $order,
            'dateFormat' => $settings->get('date_format', config('laravel-crm.date_format')),
            'email' => optional($order?->person)->getPrimaryEmail(),
            'phone' => optional($order?->person)->getPrimaryPhone(),
            'address' => optional($order?->person)->getPrimaryAddress(),
            'organization_address' => optional($order?->organization)->getPrimaryAddress(),
            'fromName' => $settings->get('organization_name'),
            'logo' => $settings->get('logo_file'),
        ];
    }

    public function getHeading(): string | Htmlable
    {
        $title = $this->record?->title;

        return $title !== null && $title !== '' ? $title : parent::getHeading();
    }
}
