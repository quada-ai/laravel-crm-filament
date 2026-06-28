<?php

namespace VentureDrake\LaravelCrmFilament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * General settings page backed by `SettingService` (`laravel-crm.settings`).
 *
 * Each form field maps to a Setting row; submit upserts via SettingService::set
 * and clears the cache. Mirrors the scalar surface of core CRM's
 * Livewire SettingEdit component (vendor/venturedrake/laravel-crm/src/Livewire/
 * Settings/SettingEdit.php).
 */
class GeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-vertical';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?string $title = 'General';

    protected static ?string $slug = 'general';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament-panels::pages.page';

    /**
     * Scalar setting keys this page edits, with a friendly label for the form
     * field AND for the `SettingService::set($key, $value, $label)` write.
     *
     * The AC story header says "25 entries" but its explicit enumeration lists
     * 26 keys; the explicit list is authoritative.
     */
    public const KEYS = [
        // Branding
        'organization_name' => 'Organization name',
        'vat_number' => 'VAT number',
        'logo_file' => 'Logo file',
        // Localisation
        'language' => 'Language',
        'country' => 'Country',
        'currency' => 'Currency',
        'timezone' => 'Timezone',
        'date_format' => 'Date format',
        'time_format' => 'Time format',
        // Prefixes
        'lead_prefix' => 'Lead prefix',
        'deal_prefix' => 'Deal prefix',
        'quote_prefix' => 'Quote prefix',
        'order_prefix' => 'Order prefix',
        'invoice_prefix' => 'Invoice prefix',
        'delivery_prefix' => 'Delivery prefix',
        'purchase_order_prefix' => 'Purchase order prefix',
        // Document defaults
        'quote_terms' => 'Quote terms',
        'invoice_contact_details' => 'Invoice contact details',
        'invoice_terms' => 'Invoice terms',
        'invoice_payment_instructions' => 'Invoice payment instructions',
        'purchase_order_terms' => 'Purchase order terms',
        'purchase_order_delivery_instructions' => 'Purchase order delivery instructions',
        // Tax
        'tax_name' => 'Tax name',
        'tax_rate' => 'Default tax rate',
        // Behaviour
        'show_related_activity' => 'Show related activity',
        'dynamic_products' => 'Dynamic products',
    ];

    public array $data = [];

    public function mount(): void
    {
        $settings = app('laravel-crm.settings');
        foreach (array_keys(static::KEYS) as $key) {
            $this->data[$key] = $settings->get($key);
        }
        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('branding')
                    ->heading(__('laravel-crm-filament::labels.sections.branding'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('organization_name')->label(static::KEYS['organization_name'])->maxLength(255),
                            TextInput::make('vat_number')->label(static::KEYS['vat_number'])->maxLength(255),
                        ]),
                        TextInput::make('logo_file')->label(static::KEYS['logo_file'])->maxLength(255),
                    ]),

                Section::make('localisation')
                    ->heading(__('laravel-crm-filament::labels.sections.localisation'))
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('language')
                                ->label(static::KEYS['language'])
                                ->options(['english' => 'English'])
                                ->searchable(),
                            Select::make('country')
                                ->label(static::KEYS['country'])
                                ->options(static::countryOptions())
                                ->searchable(),
                            Select::make('currency')
                                ->label(static::KEYS['currency'])
                                ->options(static::currencyOptions())
                                ->searchable(),
                            Select::make('timezone')
                                ->label(static::KEYS['timezone'])
                                ->options(static::timezoneOptions())
                                ->searchable(),
                            Select::make('date_format')
                                ->label(static::KEYS['date_format'])
                                ->options(static::dateFormatOptions()),
                            Select::make('time_format')
                                ->label(static::KEYS['time_format'])
                                ->options(static::timeFormatOptions()),
                        ]),
                    ]),

                Section::make('prefixes')
                    ->heading(__('laravel-crm-filament::labels.sections.prefixes'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('lead_prefix')->label(static::KEYS['lead_prefix'])->maxLength(10),
                            TextInput::make('deal_prefix')->label(static::KEYS['deal_prefix'])->maxLength(10),
                            TextInput::make('quote_prefix')->label(static::KEYS['quote_prefix'])->maxLength(10),
                            TextInput::make('order_prefix')->label(static::KEYS['order_prefix'])->maxLength(10),
                            TextInput::make('invoice_prefix')->label(static::KEYS['invoice_prefix'])->maxLength(10),
                            TextInput::make('delivery_prefix')->label(static::KEYS['delivery_prefix'])->maxLength(10),
                            TextInput::make('purchase_order_prefix')->label(static::KEYS['purchase_order_prefix'])->maxLength(10),
                        ]),
                    ]),

                Section::make('document_defaults')
                    ->heading(__('laravel-crm-filament::labels.sections.document_defaults'))
                    ->schema([
                        Textarea::make('quote_terms')->label(static::KEYS['quote_terms'])->rows(3),
                        Textarea::make('invoice_contact_details')->label(static::KEYS['invoice_contact_details'])->rows(3),
                        Textarea::make('invoice_terms')->label(static::KEYS['invoice_terms'])->rows(3),
                        Textarea::make('invoice_payment_instructions')->label(static::KEYS['invoice_payment_instructions'])->rows(3),
                        Textarea::make('purchase_order_terms')->label(static::KEYS['purchase_order_terms'])->rows(3),
                        Textarea::make('purchase_order_delivery_instructions')->label(static::KEYS['purchase_order_delivery_instructions'])->rows(3),
                    ]),

                Section::make('tax')
                    ->heading(__('laravel-crm-filament::labels.sections.tax'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('tax_name')->label(static::KEYS['tax_name'])->maxLength(255),
                            TextInput::make('tax_rate')->label(static::KEYS['tax_rate'])->numeric()->suffix('%'),
                        ]),
                    ]),

                Section::make('behaviour')
                    ->heading(__('laravel-crm-filament::labels.sections.behaviour'))
                    ->schema([
                        Toggle::make('show_related_activity')->label(static::KEYS['show_related_activity']),
                        Toggle::make('dynamic_products')->label(static::KEYS['dynamic_products']),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('laravel-crm-filament::labels.actions.save'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app('laravel-crm.settings');

        foreach (static::KEYS as $key => $label) {
            $settings->set($key, $data[$key] ?? null, $label);
        }

        if (method_exists($settings, 'forgetCache')) {
            $settings->forgetCache();
        }

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    protected static function countryOptions(): array
    {
        $options = [];
        if (function_exists('VentureDrake\\LaravelCrm\\Http\\Helpers\\SelectOptions\\countries')) {
            foreach (\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries() as $entry) {
                $name = $entry['name'] ?? null;
                if ($name) {
                    $options[$name] = $name;
                }
            }
        }

        return $options;
    }

    protected static function currencyOptions(): array
    {
        if (function_exists('VentureDrake\\LaravelCrm\\Http\\Helpers\\SelectOptions\\currencies')) {
            return \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies();
        }

        return [];
    }

    protected static function timezoneOptions(): array
    {
        if (function_exists('VentureDrake\\LaravelCrm\\Http\\Helpers\\SelectOptions\\timezones')) {
            $options = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timezones();
            unset($options['']);

            return $options;
        }

        return [];
    }

    protected static function dateFormatOptions(): array
    {
        if (function_exists('VentureDrake\\LaravelCrm\\Http\\Helpers\\SelectOptions\\dateFormats')) {
            return \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\dateFormats();
        }

        return [];
    }

    protected static function timeFormatOptions(): array
    {
        if (function_exists('VentureDrake\\LaravelCrm\\Http\\Helpers\\SelectOptions\\timeFormats')) {
            return \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timeFormats();
        }

        return [];
    }
}
