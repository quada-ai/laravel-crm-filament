<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Pages;

use BackedEnum;
use Dcblogdev\Xero\Facades\Xero;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Services\ClickSendService;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;

class Integrations extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Settings::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $title = 'Integrations';

    protected static ?string $slug = 'integrations';

    protected static ?int $navigationSort = -5;

    protected string $view = 'filament-panels::pages.page';

    public array $data = [];

    public const KEYS = [
        'xero_contacts' => 'Sync contacts to Xero',
        'xero_products' => 'Sync products to Xero',
        'xero_invoices' => 'Sync invoices to Xero',
    ];

    public function mount(): void
    {
        $settings = app('laravel-crm.settings');
        foreach (array_keys(static::KEYS) as $key) {
            $this->data[$key] = (bool) $settings->get($key);
        }
        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Xero')->heading(__('laravel-crm-filament::labels.sections.xero'))
                    ->description($this->xeroStatusLine())
                    ->schema([
                        Toggle::make('xero_contacts')->label(static::KEYS['xero_contacts']),
                        Toggle::make('xero_products')->label(static::KEYS['xero_products']),
                        Toggle::make('xero_invoices')->label(static::KEYS['xero_invoices']),
                    ]),
                Section::make('ClickSend (SMS)')->heading(__('laravel-crm-filament::labels.sections.clicksend'))
                    ->description($this->clickSendStatusLine())
                    ->schema([]),
            ]);
    }

    protected function xeroStatusLine(): string
    {
        try {
            if (class_exists(Xero::class) && Xero::isConnected()) {
                $tenant = method_exists(Xero::class, 'getTenantName')
                    ? Xero::getTenantName()
                    : 'Connected';

                return "Connected to Xero: {$tenant}";
            }
        } catch (\Throwable $e) {
            return 'Xero status unavailable';
        }

        return 'Not connected to Xero.';
    }

    protected function clickSendStatusLine(): string
    {
        $cs = app(ClickSendService::class);
        if (! $cs->isConfigured()) {
            return 'ClickSend credentials not configured.';
        }
        $check = $cs->verifyCredentials();
        if (! ($check['ok'] ?? false)) {
            return 'ClickSend credentials configured but verification failed: ' . ($check['error'] ?? 'unknown error');
        }
        $balance = $check['balance'] ?? null;

        return 'ClickSend connected. Balance: ' . ($balance !== null ? $balance : 'unknown') . '.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connectXero')
                ->label(__('laravel-crm-filament::labels.actions.connect_xero'))
                ->icon('heroicon-o-link')
                ->color('primary')
                ->url(fn () => route('laravel-crm.integrations.xero.connect'))
                ->visible(fn () => ! $this->xeroIsConnected()),
            Action::make('disconnectXero')
                ->label(__('laravel-crm-filament::labels.actions.disconnect_xero'))
                ->icon('heroicon-o-link-slash')
                ->color('danger')
                ->requiresConfirmation()
                ->url(fn () => route('laravel-crm.integrations.xero.disconnect'))
                ->visible(fn () => $this->xeroIsConnected()),
        ];
    }

    protected function xeroIsConnected(): bool
    {
        try {
            return class_exists(Xero::class)
                && Xero::isConnected();
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label(__('laravel-crm-filament::labels.actions.save_sync_settings'))->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app('laravel-crm.settings');

        foreach (static::KEYS as $key => $label) {
            $settings->set($key, $data[$key] ? '1' : '0', $label);
        }

        if (method_exists($settings, 'forgetCache')) {
            $settings->forgetCache();
        }

        Notification::make()->title('Sync settings saved')->success()->send();
    }
}
