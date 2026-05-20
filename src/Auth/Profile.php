<?php

namespace VentureDrake\LaravelCrmFilament\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Pages\Reminders;

/**
 * Branded profile page for the CRM panel. Wraps the stock fields in
 * sections, adds an avatar upload, and links out to the v0.10
 * Reminders settings page for notification preferences.
 */
class Profile extends EditProfile
{
    public function getHeading(): string | Htmlable | null
    {
        $name = app('laravel-crm.settings')->get('organization_name') ?: config('app.name');

        return new HtmlString(e($name) . ' &middot; Profile');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Avatar')
                    ->description('Upload a profile photo. Saved alongside your user record.')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->avatar()
                            ->disk(config('laravel-crm.upload_disk', 'public'))
                            ->directory('crm/avatars')
                            ->maxSize(2048)
                            ->visibility('public'),
                    ]),

                Section::make('Account')
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent(),
                    ]),

                Section::make('Notification preferences')
                    ->description('Manage which CRM reminders you receive and how far in advance.')
                    ->schema([
                        Placeholder::make('reminders_link')
                            ->label('')
                            ->content(new HtmlString(
                                '<a href="' . e(Reminders::getUrl()) . '" '
                                . 'class="fi-link inline-flex items-center gap-1 text-primary-600 hover:underline" '
                                . 'style="color:#05b3a9;">Open Reminders settings &rarr;</a>'
                            )),
                    ]),
            ]);
    }

    protected function fillForm(): void
    {
        parent::fillForm();

        $this->data['avatar'] = Setting::query()
            ->where('name', $this->avatarSettingKey())
            ->value('value');
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        $avatar = $data['avatar'] ?? null;
        unset($data['avatar']);

        $record = parent::handleRecordUpdate($record, $data);

        Setting::updateOrCreate(
            ['name' => $this->avatarSettingKey()],
            ['value' => $avatar, 'label' => 'Avatar'],
        );

        return $record;
    }

    protected function avatarSettingKey(): string
    {
        return 'user.' . $this->getUser()->getKey() . '.avatar';
    }
}
