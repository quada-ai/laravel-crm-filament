<?php

namespace VentureDrake\LaravelCrmFilament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Generic settings page backed by `SettingService` (`laravel-crm.settings`).
 * Each form field maps to a Setting row; submit upserts via SettingService::set
 * and clears the cache.
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
     * Setting keys this page edits, with a friendly label for the form field.
     * Keep this list narrow; complex settings get dedicated pages later.
     */
    public const KEYS = [
        'organization_name' => 'Organization name',
        'logo_file' => 'Logo file',
        'date_format' => 'Date format',
        'default_currency' => 'Default currency',
        'quote_prefix' => 'Quote prefix',
        'order_prefix' => 'Order prefix',
        'invoice_prefix' => 'Invoice prefix',
        'purchase_order_prefix' => 'Purchase order prefix',
        'tax_rate' => 'Default tax rate',
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
                Section::make('Branding')->heading(__('laravel-crm-filament::labels.sections.branding'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('organization_name')->label(static::KEYS['organization_name'])->maxLength(255),
                            TextInput::make('logo_file')->label(static::KEYS['logo_file'])->maxLength(255),
                        ]),
                    ]),
                Section::make('Formatting')->heading(__('laravel-crm-filament::labels.sections.formatting'))
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('date_format')
                                ->label(static::KEYS['date_format'])
                                ->options([
                                    'd/m/Y' => 'DD/MM/YYYY',
                                    'm/d/Y' => 'MM/DD/YYYY',
                                    'Y-m-d' => 'YYYY-MM-DD',
                                ]),
                            TextInput::make('default_currency')->label(static::KEYS['default_currency'])->maxLength(3),
                        ]),
                    ]),
                Section::make('Prefixes')->heading(__('laravel-crm-filament::labels.sections.prefixes'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('quote_prefix')->label(static::KEYS['quote_prefix'])->maxLength(10),
                            TextInput::make('order_prefix')->label(static::KEYS['order_prefix'])->maxLength(10),
                            TextInput::make('invoice_prefix')->label(static::KEYS['invoice_prefix'])->maxLength(10),
                            TextInput::make('purchase_order_prefix')->label(static::KEYS['purchase_order_prefix'])->maxLength(10),
                        ]),
                    ]),
                Section::make('Tax')->heading(__('laravel-crm-filament::labels.sections.tax'))
                    ->schema([
                        TextInput::make('tax_rate')->label(static::KEYS['tax_rate'])->numeric()->suffix('%'),
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
}
