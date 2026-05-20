<?php

namespace VentureDrake\LaravelCrmFilament\Auth;

use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\QRCode\Chillerlan;
use Throwable;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Pages\Reminders;

/**
 * Branded profile page for the CRM panel.
 *
 * Extends Filament's EditProfile with:
 *  - Avatar upload + name/email account form (the standard form)
 *  - Standalone "Update password" form requiring current password +
 *    confirmation, using the same min:8/confirmed rules as core CRM.
 *  - Two-factor authentication enrolment (QR code, recovery codes,
 *    enable/confirm/disable) driven by pragmarx/google2fa, modeled on
 *    core's Livewire TwoFactorAuthenticationForm.
 *  - Browser sessions list with "Log out other sessions" action,
 *    mirroring core's LogoutOtherBrowserSessionsForm.
 *  - Notification preferences link to the v0.10 Reminders settings page.
 *
 * Renders via a custom Blade view at
 * `laravel-crm-filament::auth.profile` so QR code / recovery code /
 * session-list HTML can sit alongside the Filament forms.
 */
class Profile extends EditProfile
{
    protected string $view = 'laravel-crm-filament::auth.profile';

    /** @var array<string, mixed> */
    public ?array $passwordData = [
        'current_password' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public string $twoFactorCode = '';

    public string $logoutPassword = '';

    public bool $showingQrCode = false;

    public bool $showingRecoveryCodes = false;

    public bool $confirmingTwoFactor = false;

    public function mount(): void
    {
        parent::mount();

        $this->passwordForm->fill([]);
    }

    public function getHeading(): string | Htmlable | null
    {
        $name = app('laravel-crm.settings')->get('organization_name') ?: config('app.name');

        return new HtmlString(e($name) . ' &middot; Profile');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model($this->getUser())
            ->operation('edit')
            ->components([
                Section::make('Avatar')->heading(__('laravel-crm-filament::labels.sections.avatar'))
                    ->description('Upload a profile photo. Saved alongside your user record.')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label(__('laravel-crm-filament::labels.file.avatar'))
                            ->image()
                            ->avatar()
                            ->disk(config('laravel-crm.upload_disk', 'public'))
                            ->directory('crm/avatars')
                            ->maxSize(2048)
                            ->visibility('public'),
                    ]),

                Section::make('Account')->heading(__('laravel-crm-filament::labels.sections.account'))
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                    ]),

                Section::make('Notification preferences')->heading(__('laravel-crm-filament::labels.sections.notification_preferences'))
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

    public function passwordForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('passwordData')
            ->components([
                Section::make('Update password')->heading(__('laravel-crm-filament::labels.sections.update_password'))
                    ->description('Choose a new password. Enter your current password to confirm the change.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label(__('laravel-crm-filament::labels.fields.current_password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('current-password')
                            ->required()
                            ->rule('current_password'),

                        TextInput::make('password')
                            ->label(__('laravel-crm-filament::labels.fields.new_password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required()
                            ->minLength(8)
                            ->confirmed(),

                        TextInput::make('password_confirmation')
                            ->label(__('laravel-crm-filament::labels.fields.confirm_password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required(),
                    ]),
            ]);
    }

    public function updatePassword(): void
    {
        $state = $this->passwordForm->getState();

        $this->getUser()->forceFill([
            'password' => Hash::make($state['password']),
        ])->save();

        $this->passwordData = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->passwordForm->fill($this->passwordData);

        Notification::make()
            ->title('Password updated')
            ->success()
            ->send();
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

    public function isTwoFactorEnabled(): bool
    {
        $user = $this->getUser();

        if (! $this->hasTwoFactorColumns($user)) {
            return false;
        }

        return ! is_null($user->two_factor_secret ?? null)
            && (
                ! DbSchema::hasColumn($user->getTable(), 'two_factor_confirmed_at')
                || ! is_null($user->two_factor_confirmed_at ?? null)
            );
    }

    public function isTwoFactorPending(): bool
    {
        $user = $this->getUser();

        if (! $this->hasTwoFactorColumns($user)) {
            return false;
        }

        return ! is_null($user->two_factor_secret ?? null)
            && DbSchema::hasColumn($user->getTable(), 'two_factor_confirmed_at')
            && is_null($user->two_factor_confirmed_at ?? null);
    }

    public function hasTwoFactorColumns(?Model $user = null): bool
    {
        $user ??= $this->getUser();

        return DbSchema::hasColumn($user->getTable(), 'two_factor_secret')
            && DbSchema::hasColumn($user->getTable(), 'two_factor_recovery_codes');
    }

    public function enableTwoFactor(): void
    {
        $user = $this->getUser();

        if (! $this->hasTwoFactorColumns($user)) {
            Notification::make()
                ->title('Two-factor authentication is not configured')
                ->body('Add the two_factor_secret and two_factor_recovery_codes columns to the users table to enable this feature.')
                ->danger()
                ->send();

            return;
        }

        $google2fa = $this->google2fa();

        $user->forceFill([
            'two_factor_secret' => encrypt($google2fa->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(
                Collection::times(8, fn () => $this->generateRecoveryCode())->all()
            )),
        ]);

        if (DbSchema::hasColumn($user->getTable(), 'two_factor_confirmed_at')) {
            $user->two_factor_confirmed_at = null;
        }

        $user->save();

        $this->showingQrCode = true;
        $this->confirmingTwoFactor = DbSchema::hasColumn($user->getTable(), 'two_factor_confirmed_at');
        $this->showingRecoveryCodes = ! $this->confirmingTwoFactor;
        $this->twoFactorCode = '';
    }

    public function confirmTwoFactor(): void
    {
        $this->validate(['twoFactorCode' => ['required', 'string']]);

        $user = $this->getUser();

        if (! $user->two_factor_secret) {
            return;
        }

        $valid = $this->google2fa()->verifyKey(
            decrypt($user->two_factor_secret),
            $this->twoFactorCode
        );

        if (! $valid) {
            $this->addError('twoFactorCode', __('The provided two factor authentication code was invalid.'));

            return;
        }

        if (DbSchema::hasColumn($user->getTable(), 'two_factor_confirmed_at')) {
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        }

        $this->confirmingTwoFactor = false;
        $this->twoFactorCode = '';
        $this->showingRecoveryCodes = true;

        Notification::make()
            ->title('Two-factor authentication enabled')
            ->success()
            ->send();
    }

    public function regenerateRecoveryCodes(): void
    {
        $user = $this->getUser();

        if (! $this->hasTwoFactorColumns($user)) {
            return;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(
                Collection::times(8, fn () => $this->generateRecoveryCode())->all()
            )),
        ])->save();

        $this->showingRecoveryCodes = true;

        Notification::make()
            ->title('Recovery codes regenerated')
            ->success()
            ->send();
    }

    public function showRecoveryCodes(): void
    {
        $this->showingRecoveryCodes = true;
    }

    public function disableTwoFactor(): void
    {
        $user = $this->getUser();

        if (! $this->hasTwoFactorColumns($user)) {
            return;
        }

        $fields = ['two_factor_secret' => null, 'two_factor_recovery_codes' => null];

        if (DbSchema::hasColumn($user->getTable(), 'two_factor_confirmed_at')) {
            $fields['two_factor_confirmed_at'] = null;
        }

        $user->forceFill($fields)->save();

        $this->showingQrCode = false;
        $this->showingRecoveryCodes = false;
        $this->confirmingTwoFactor = false;
        $this->twoFactorCode = '';

        Notification::make()
            ->title('Two-factor authentication disabled')
            ->success()
            ->send();
    }

    public function getQrCodeSvg(): ?string
    {
        $user = $this->getUser();

        if (! $this->hasTwoFactorColumns($user) || ! $user->two_factor_secret) {
            return null;
        }

        try {
            $url = $this->google2fa()->getQRCodeUrl(
                config('app.name', 'Laravel'),
                (string) $user->email,
                decrypt($user->two_factor_secret)
            );
        } catch (Throwable) {
            return null;
        }

        if (class_exists(\PragmaRX\Google2FAQRCode\Google2FA::class)
            && class_exists(QRCode::class)) {
            try {
                $qr = new \PragmaRX\Google2FAQRCode\Google2FA(
                    new Chillerlan
                );

                return $qr->getQRCodeInline(
                    config('app.name', 'Laravel'),
                    (string) $user->email,
                    decrypt($user->two_factor_secret),
                );
            } catch (Throwable) {
                // fall through
            }
        }

        return $url;
    }

    public function getSetupKey(): ?string
    {
        $user = $this->getUser();

        if (! $this->hasTwoFactorColumns($user) || ! $user->two_factor_secret) {
            return null;
        }

        try {
            return decrypt($user->two_factor_secret);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    public function getRecoveryCodes(): array
    {
        $user = $this->getUser();

        if (! $this->hasTwoFactorColumns($user) || ! $user->two_factor_recovery_codes) {
            return [];
        }

        try {
            $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        } catch (Throwable) {
            return [];
        }

        return is_array($codes) ? $codes : [];
    }

    public function logoutOtherBrowserSessions(): void
    {
        $this->validate([
            'logoutPassword' => ['required', 'string', 'current_password'],
        ]);

        Auth::logoutOtherDevices($this->logoutPassword);

        $this->deleteOtherSessionRecords();

        $this->logoutPassword = '';

        Notification::make()
            ->title('Logged out other browser sessions')
            ->success()
            ->send();
    }

    protected function deleteOtherSessionRecords(): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        if (! $this->sessionsTableExists()) {
            return;
        }

        $currentId = request()->hasSession() ? request()->session()->getId() : null;

        $query = DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $this->getUser()->getAuthIdentifier());

        if ($currentId !== null) {
            $query->where('id', '!=', $currentId);
        }

        $query->delete();
    }

    /**
     * @return Collection<int, object>
     */
    public function getSessions(): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        if (! $this->sessionsTableExists()) {
            return collect();
        }

        $rows = DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $this->getUser()->getAuthIdentifier())
            ->orderBy('last_activity', 'desc')
            ->get();

        return collect($rows)->map(function ($session) {
            return (object) [
                'id' => $session->id,
                'agent' => $this->summariseAgent($session->user_agent ?? null),
                'ip_address' => $session->ip_address ?? null,
                'is_current_device' => request()->hasSession() && $session->id === request()->session()->getId(),
                'last_active' => Carbon::createFromTimestamp((int) $session->last_activity)->diffForHumans(),
            ];
        });
    }

    protected function sessionsTableExists(): bool
    {
        return DbSchema::connection(config('session.connection'))
            ->hasTable(config('session.table', 'sessions'));
    }

    /**
     * @return array{is_desktop: bool, platform: string, browser: string}
     */
    protected function summariseAgent(?string $userAgent): array
    {
        $userAgent = (string) $userAgent;

        $platform = 'Unknown';
        foreach (['Windows', 'Macintosh', 'Linux', 'iPhone', 'iPad', 'Android'] as $os) {
            if (Str::contains($userAgent, $os)) {
                $platform = $os;

                break;
            }
        }

        $browser = 'Unknown';
        foreach (['Edg' => 'Edge', 'Chrome' => 'Chrome', 'Safari' => 'Safari', 'Firefox' => 'Firefox', 'OPR' => 'Opera'] as $needle => $name) {
            if (Str::contains($userAgent, $needle)) {
                $browser = $name;

                break;
            }
        }

        return [
            'is_desktop' => ! Str::contains($userAgent, ['Mobile', 'Android', 'iPhone']),
            'platform' => $platform,
            'browser' => $browser,
        ];
    }

    protected function google2fa(): Google2FA
    {
        return app(Google2FA::class);
    }

    protected function generateRecoveryCode(): string
    {
        return Str::random(10) . '-' . Str::random(10);
    }
}
