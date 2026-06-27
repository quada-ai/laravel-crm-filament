<?php

namespace VentureDrake\LaravelCrmFilament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\Setting;

/**
 * Per-user reminder preferences. One toggle + "hours before" input per type;
 * persisted as Setting rows keyed `reminders.{user_id}.{type}.{hours_before}`.
 *
 * Setting NAME (the key) carries the entire shape, including the hours-before
 * value the user picked, so each toggle write replaces the previous row(s)
 * for that (user, type) pair. The Setting VALUE is "1" / "0" for on / off.
 */
class Reminders extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $title = 'Reminders';

    protected static ?string $slug = 'reminders';

    protected static ?int $navigationSort = 80;

    protected string $view = 'laravel-crm-filament::clusters.settings.pages.reminders';

    public const TYPES = [
        'task' => 'Tasks',
        'call' => 'Calls',
        'meeting' => 'Meetings',
        'lunch' => 'Lunches',
    ];

    public array $data = [];

    public function mount(): void
    {
        $userId = (int) (auth()->id() ?? 0);
        $settings = app('laravel-crm.settings');

        foreach (array_keys(static::TYPES) as $type) {
            [$enabled, $hours] = $this->readReminder($settings, $userId, $type);
            $this->data["{$type}_enabled"] = $enabled;
            $this->data["{$type}_hours"] = $hours;
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        $components = [];
        foreach (static::TYPES as $type => $label) {
            $components[] = Section::make($label)
                ->key("section_{$type}")
                ->schema([
                    Grid::make(2)->schema([
                        Checkbox::make("{$type}_enabled")
                            ->label("Email me before each {$type}"),
                        TextInput::make("{$type}_hours")
                            ->label(__('laravel-crm-filament::labels.misc.hours_before'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(168)
                            ->default(1),
                    ]),
                ]);
        }

        return $schema->statePath('data')->components($components);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('laravel-crm-filament::labels.actions.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $userId = (int) (auth()->id() ?? 0);
        $settings = app('laravel-crm.settings');

        foreach (array_keys(static::TYPES) as $type) {
            $enabled = (bool) ($state["{$type}_enabled"] ?? false);
            $hours = (int) max(0, (int) ($state["{$type}_hours"] ?? 1));

            $this->writeReminder($settings, $userId, $type, $enabled, $hours);
        }

        if (method_exists($settings, 'forgetCache')) {
            $settings->forgetCache();
        }

        Notification::make()
            ->title('Reminder preferences saved')
            ->success()
            ->send();
    }

    /**
     * @return array{0:bool,1:int}
     */
    protected function readReminder(mixed $settings, int $userId, string $type): array
    {
        $prefix = "reminders.{$userId}.{$type}.";
        $all = method_exists($settings, 'all') ? (array) $settings->all() : [];

        foreach ($all as $name => $value) {
            if (str_starts_with((string) $name, $prefix)) {
                $hours = (int) substr((string) $name, strlen($prefix));

                return [(bool) (int) $value, $hours];
            }
        }

        return [false, 1];
    }

    protected function writeReminder(mixed $settings, int $userId, string $type, bool $enabled, int $hours): void
    {
        $prefix = "reminders.{$userId}.{$type}.";
        $all = method_exists($settings, 'all') ? (array) $settings->all() : [];

        foreach (array_keys($all) as $name) {
            if (str_starts_with((string) $name, $prefix)) {
                Setting::query()
                    ->where('name', $name)
                    ->delete();
            }
        }

        $settings->set(
            $prefix . $hours,
            $enabled ? '1' : '0',
            ucfirst($type) . ' reminder',
        );
    }
}
