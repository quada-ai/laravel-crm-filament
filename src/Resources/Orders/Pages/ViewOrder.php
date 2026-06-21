<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Orders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrmFilament\Concerns\DownloadsPdf;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;

class ViewOrder extends ViewRecord
{
    use Concerns\HasOrderConvertToDeliveryAction;
    use Concerns\HasOrderConvertToPurchaseOrderAction;
    use DownloadsPdf;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            OrderResource::backToIndexAction(),
            $this->orderConvertToDeliveryAction()
                ->label(__('laravel-crm-filament::labels.actions.delivery')),
            $this->orderConvertToPurchaseOrderAction()
                ->label(__('laravel-crm-filament::labels.actions.purchase_order')),
            $this->downloadPdfAction(fn (Order $record) => $this->streamPdfDownload(
                $record,
                'order',
                'order',
                'laravel-crm::orders.pdf',
                $this->orderPdfViewData($record),
            ))
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-arrow-down-tray'),
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

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                $this->getInfolistContentComponent()->columnSpan(['lg' => 1]),
                $this->getRelationManagersContentComponent()->columnSpan(['lg' => 1]),
            ]),
        ]);
    }

    protected function orderPdfViewData(Order $record): array
    {
        $settings = app('laravel-crm.settings');

        return [
            'order' => $record,
            'dateFormat' => $settings->get('date_format', config('laravel-crm.date_format')),
            'email' => optional($record->person)->getPrimaryEmail(),
            'phone' => optional($record->person)->getPrimaryPhone(),
            'address' => optional($record->person)->getPrimaryAddress(),
            'organization_address' => optional($record->organization)->getPrimaryAddress(),
            'fromName' => $settings->get('organization_name'),
            'logo' => $settings->get('logo_file'),
        ];
    }

    public function getTitle(): string | Htmlable
    {
        return $this->record?->title ?? parent::getTitle();
    }
}
