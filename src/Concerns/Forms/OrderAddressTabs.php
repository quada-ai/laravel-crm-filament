<?php

namespace VentureDrake\LaravelCrmFilament\Concerns\Forms;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Collection;
use VentureDrake\LaravelCrm\Models\AddressType;

/**
 * Tabbed Billing / Shipping addresses card for the Order create/edit form.
 *
 * Filament form state for the two tabs is flat (no nested array) — the
 * keys are `billing_address` and `shipping_address`. Use the static
 * helpers `toFormData(Order $order)` / `fromFormData(array $data)` on
 * the Create + Edit pages to flatten / re-inflate against the
 * `addresses[]` array shape that `OrderService::create/update` consumes.
 */
class OrderAddressTabs
{
    public static function make(): Section
    {
        return Section::make(__('laravel-crm-filament::labels.contact.addresses'))
            ->schema([
                Tabs::make('addresses_tabs')
                    ->tabs([
                        Tab::make(__('laravel-crm-filament::labels.contact.billing'))
                            ->schema(self::addressFields('billing_address')),

                        Tab::make(__('laravel-crm-filament::labels.contact.shipping'))
                            ->schema(self::addressFields('shipping_address')),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Convert the parent record's persisted addresses into the form's
     * tabbed-state shape. The returned array has keys
     * `billing_address` + `shipping_address`, each itself an associative
     * array of the address fields.
     */
    public static function toFormData(Collection $addresses): array
    {
        $byType = [];
        foreach ($addresses as $a) {
            $byType[(int) $a->address_type_id] = $a;
        }

        $billingId = self::billingTypeId();
        $shippingId = self::shippingTypeId();

        return [
            'billing_address' => self::addressToArray($byType[$billingId] ?? null),
            'shipping_address' => self::addressToArray($byType[$shippingId] ?? null),
        ];
    }

    /**
     * Re-inflate the form's tabbed state into the `addresses[]` shape
     * that OrderService::create/update consumes.
     */
    public static function fromFormData(array $data): array
    {
        $addresses = [];

        $billing = $data['billing_address'] ?? null;
        if (is_array($billing) && self::hasAnyField($billing)) {
            $addresses[] = array_merge($billing, [
                'address_type_id' => self::billingTypeId(),
            ]);
        }

        $shipping = $data['shipping_address'] ?? null;
        if (is_array($shipping) && self::hasAnyField($shipping)) {
            $addresses[] = array_merge($shipping, [
                'address_type_id' => self::shippingTypeId(),
            ]);
        }

        return $addresses;
    }

    /**
     * @return array<Forms\Components\Component>
     */
    protected static function addressFields(string $prefix): array
    {
        return [
            Forms\Components\Hidden::make($prefix . '.id'),
            Grid::make(2)->schema([
                Forms\Components\TextInput::make($prefix . '.contact')
                    ->label(__('laravel-crm-filament::labels.fields.contact'))
                    ->maxLength(255),
                Forms\Components\TextInput::make($prefix . '.phone')
                    ->label(__('laravel-crm-filament::labels.contact.phone'))
                    ->tel()
                    ->maxLength(50),
            ]),
            Forms\Components\TextInput::make($prefix . '.line1')
                ->label(__('laravel-crm-filament::labels.contact.line1'))
                ->maxLength(255),
            Forms\Components\TextInput::make($prefix . '.line2')
                ->label(__('laravel-crm-filament::labels.contact.line2'))
                ->maxLength(255),
            Forms\Components\TextInput::make($prefix . '.line3')
                ->label(__('laravel-crm-filament::labels.contact.line3'))
                ->maxLength(255),
            Grid::make(2)->schema([
                Forms\Components\TextInput::make($prefix . '.city')
                    ->label(__('laravel-crm-filament::labels.contact.city'))
                    ->maxLength(100),
                Forms\Components\TextInput::make($prefix . '.state')
                    ->label(__('laravel-crm-filament::labels.contact.state'))
                    ->maxLength(100),
            ]),
            Grid::make(2)->schema([
                Forms\Components\TextInput::make($prefix . '.code')
                    ->label(__('laravel-crm-filament::labels.contact.post_code'))
                    ->maxLength(20),
                Forms\Components\TextInput::make($prefix . '.country')
                    ->label(__('laravel-crm-filament::labels.contact.country'))
                    ->maxLength(100),
            ]),
        ];
    }

    protected static function addressToArray($address): array
    {
        if (! $address) {
            return [];
        }

        return [
            'id' => $address->id,
            'contact' => $address->contact,
            'phone' => $address->phone,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'line3' => $address->line3,
            'city' => $address->city,
            'state' => $address->state,
            'code' => $address->code,
            'country' => $address->country,
        ];
    }

    protected static function hasAnyField(array $address): bool
    {
        foreach (['contact', 'phone', 'line1', 'line2', 'line3', 'city', 'state', 'code', 'country'] as $key) {
            if (! empty($address[$key])) {
                return true;
            }
        }

        return false;
    }

    protected static function billingTypeId(): ?int
    {
        return AddressType::query()->where('name', 'Billing')->value('id');
    }

    protected static function shippingTypeId(): ?int
    {
        return AddressType::query()->where('name', 'Shipping')->value('id');
    }
}
