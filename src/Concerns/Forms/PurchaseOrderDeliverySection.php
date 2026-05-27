<?php

namespace VentureDrake\LaravelCrmFilament\Concerns\Forms;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Organization;

/**
 * "Delivery Details" left-column Section for the PurchaseOrder form.
 *
 * Renders:
 *   delivery_type           Select live (deliver / collect)
 *   delivery_address        Select (visible when delivery_type='deliver')
 *   delivery_instructions   Textarea (visible when delivery_type='deliver')
 *
 * The address option source pulls from `crm_addresses` rows attached to
 * Organizations, which is the same shape `PurchaseOrderService::create`
 * reads via `Address::find($request->delivery_address)`.
 */
class PurchaseOrderDeliverySection
{
    public static function make(): Section
    {
        return Section::make(__('laravel-crm-filament::labels.sections.delivery_details'))
            ->schema([
                Grid::make(1)->schema([
                    Forms\Components\Select::make('delivery_type')
                        ->label(__('laravel-crm-filament::labels.sales.delivery_type'))
                        ->options([
                            'deliver' => __('laravel-crm-filament::labels.sales.deliver'),
                            'collect' => __('laravel-crm-filament::labels.sales.collect'),
                        ])
                        ->live()
                        ->default('deliver'),

                    Forms\Components\Select::make('delivery_address')
                        ->label(__('laravel-crm-filament::labels.sales.delivery_address'))
                        ->options(fn () => self::deliveryAddressOptions())
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => $get('delivery_type') === 'deliver'),

                    Forms\Components\Textarea::make('delivery_instructions')
                        ->label(__('laravel-crm-filament::labels.sales.delivery_instructions'))
                        ->rows(3)
                        ->columnSpanFull()
                        ->visible(fn (Get $get) => $get('delivery_type') === 'deliver'),
                ]),
            ]);
    }

    /**
     * Build the delivery address option list from Organization addresses.
     * Each entry's label is "Org Name — line1, city".
     */
    protected static function deliveryAddressOptions(): array
    {
        $rows = Address::query()
            ->where('addressable_type', Organization::class)
            ->orderBy('id')
            ->get();

        $orgs = Organization::query()
            ->whereIn('id', $rows->pluck('addressable_id')->unique())
            ->get()
            ->keyBy('id');

        $options = [];
        foreach ($rows as $address) {
            $org = $orgs->get($address->addressable_id);
            $orgName = $org->name ?? 'Organization';
            $location = implode(', ', array_filter([
                $address->line1 ?? null,
                $address->city ?? $address->suburb ?? null,
            ]));
            $options[$address->id] = $orgName . ($location !== '' ? ' — ' . $location : '');
        }

        return $options;
    }
}
