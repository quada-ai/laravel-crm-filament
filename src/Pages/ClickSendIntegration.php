<?php

namespace VentureDrake\LaravelCrmFilament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Services\ClickSendService;

/**
 * Dedicated ClickSend SMS integration page. Persists the three core
 * credentials (`clicksend_username`, `clicksend_api_key`,
 * `clicksend_default_from`) via SettingService so the values are
 * shared with the existing Livewire UI, and exposes a "Send test SMS"
 * header action that round-trips through ClickSendService::sendSms().
 */
class ClickSendIntegration extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?string $title = 'ClickSend (SMS)';

    protected static ?string $slug = 'clicksend';

    protected static ?int $navigationSort = 115;

    protected string $view = 'filament-panels::pages.page';

    public const KEYS = [
        'clicksend_username' => 'ClickSend username',
        'clicksend_api_key' => 'ClickSend API key',
        'clicksend_default_from' => 'Default sender ID',
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
                Section::make('ClickSend (SMS)')
                    ->heading(__('laravel-crm-filament::labels.sections.clicksend'))
                    ->description($this->statusLine())
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('clicksend_username')
                                ->label(__('laravel-crm-filament::labels.fields.clicksend_username'))
                                ->maxLength(255),
                            TextInput::make('clicksend_api_key')
                                ->label(__('laravel-crm-filament::labels.fields.clicksend_api_key'))
                                ->password()
                                ->revealable()
                                ->maxLength(255),
                            TextInput::make('clicksend_default_from')
                                ->label(__('laravel-crm-filament::labels.fields.clicksend_default_from'))
                                ->maxLength(32),
                        ]),
                    ]),
            ]);
    }

    protected function statusLine(): string
    {
        $cs = app(ClickSendService::class);
        if (! $cs->isConfigured()) {
            return 'ClickSend credentials not configured.';
        }

        try {
            $check = $cs->verifyCredentials();
        } catch (\Throwable $e) {
            return 'ClickSend credentials configured but verification failed: ' . $e->getMessage();
        }
        if (! ($check['ok'] ?? false)) {
            return 'ClickSend credentials configured but verification failed: ' . ($check['error'] ?? 'unknown error');
        }
        $balance = $check['balance'] ?? null;

        return 'ClickSend connected. Balance: ' . ($balance !== null ? $balance : 'unknown') . '.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('laravel-crm-filament::labels.actions.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
            Action::make('sendTestSms')
                ->label(__('laravel-crm-filament::labels.actions.send_test_sms'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->schema([
                    TextInput::make('to')
                        ->label(__('laravel-crm-filament::labels.fields.phone_number'))
                        ->required()
                        ->helperText('Use E.164 format, e.g. +15551234567'),
                    TextInput::make('body')
                        ->label(__('laravel-crm-filament::labels.fields.message'))
                        ->required()
                        ->maxLength(160)
                        ->default('Test SMS from laravel-crm-filament.'),
                ])
                ->action(function (array $data): void {
                    $service = app(ClickSendService::class);
                    $service->refresh();

                    if (! $service->isConfigured()) {
                        Notification::make()
                            ->title('ClickSend not configured')
                            ->body('Save your ClickSend credentials before sending a test SMS.')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $result = $service->sendSms(
                            (string) $data['to'],
                            (string) $data['body'],
                            $service->defaultFrom(),
                            'filament-test-sms',
                        );
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Test SMS failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($result['ok'] ?? false) {
                        Notification::make()
                            ->title('Test SMS sent')
                            ->body('Message ID: ' . ($result['message_id'] ?? 'n/a'))
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Test SMS failed')
                        ->body($result['error'] ?? 'ClickSend rejected the message.')
                        ->danger()
                        ->send();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('saveForm')
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

        app(ClickSendService::class)->refresh();

        Notification::make()
            ->title('ClickSend settings saved')
            ->success()
            ->send();
    }
}
