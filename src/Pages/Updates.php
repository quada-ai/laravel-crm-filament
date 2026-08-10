<?php

namespace VentureDrake\LaravelCrmFilament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class Updates extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $slug = 'updates';

    protected static ?int $navigationSort = 200;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return false;
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('laravel-crm-filament::labels.pages.updates.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('laravel-crm-filament::labels.pages.updates.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return \VentureDrake\LaravelCrmFilament\LaravelCrmPlugin::get()->getNavigationGroup()
            ?? __('laravel-crm-filament::labels.navigation.groups.settings');
    }

    protected string $view = 'laravel-crm-filament::clusters.settings.pages.updates';

    public ?string $currentVersion = null;

    public ?string $latestVersion = null;

    public ?string $installId = null;

    public ?string $releaseNotes = null;

    public function mount(): void
    {
        $this->currentVersion = (string) (config('laravel-crm.version') ?? '');

        $settings = app()->bound('laravel-crm.settings') ? app('laravel-crm.settings') : null;
        if ($settings) {
            $this->latestVersion = $settings->get('version_latest') ?: null;
            $this->installId = $settings->get('install_id') ?: null;
            $this->releaseNotes = $settings->get('version_latest_notes') ?: null;
        }
    }

    public function getIsUpToDateProperty(): bool
    {
        if (! $this->latestVersion || ! $this->currentVersion) {
            return true;
        }

        return version_compare($this->currentVersion, $this->latestVersion, '>=');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkForUpdates')
                ->label(__('laravel-crm-filament::labels.actions.check_for_updates'))
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    Artisan::queue('laravelcrm:update');

                    Notification::make()
                        ->title('Update queued')
                        ->body('The laravelcrm:update command has been queued. Refresh shortly to see the new version.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
