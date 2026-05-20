<?php

use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use VentureDrake\LaravelCrmFilament\Auth\Profile;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

beforeEach(function () {
    $this->password = 'oldpassword123';
    $this->user = User::create([
        'name' => 'Profile Tester',
        'email' => 'profile-tester-' . uniqid() . '@example.com',
        'password' => Hash::make($this->password),
    ]);
    $this->actingAs($this->user);
});

it('Profile extends Filament\'s EditProfile page', function () {
    expect(is_subclass_of(Profile::class, EditProfile::class))->toBeTrue();
});

it('Profile preserves avatar, name, email, and Reminders link in the main form', function () {
    $source = file_get_contents((new ReflectionClass(Profile::class))->getFileName());

    // Avatar section + FileUpload
    expect($source)->toContain("FileUpload::make('avatar')");
    expect($source)->toContain("Section::make('Avatar')");

    // Account section (name + email)
    expect($source)->toContain("Section::make('Account')");
    expect($source)->toContain('getNameFormComponent()');
    expect($source)->toContain('getEmailFormComponent()');

    // Reminders link
    expect($source)->toContain('Reminders::getUrl');
    expect($source)->toContain('Notification preferences');
});

it('Profile declares a separate passwordForm with current_password + confirmation', function () {
    expect(method_exists(Profile::class, 'passwordForm'))->toBeTrue();
    expect(method_exists(Profile::class, 'updatePassword'))->toBeTrue();

    $source = file_get_contents((new ReflectionClass(Profile::class))->getFileName());

    expect($source)->toContain("TextInput::make('current_password')");
    expect($source)->toContain("TextInput::make('password')");
    expect($source)->toContain("TextInput::make('password_confirmation')");
    // Same validation contract as core CRM: current_password + min 8 + confirmed.
    expect($source)->toContain("->rule('current_password')");
    expect($source)->toContain('->minLength(8)');
    expect($source)->toContain('->confirmed()');
});

it('updatePassword() persists a hashed new password when credentials match', function () {
    $page = new Profile;
    $page->passwordData = [
        'current_password' => $this->password,
        'password' => 'NewPassword!23',
        'password_confirmation' => 'NewPassword!23',
    ];

    // Drive the password form through Filament's form lifecycle so the
    // `current_password` rule + `confirmed` rule both run.
    $page->passwordForm->fill($page->passwordData);
    $page->updatePassword();

    $this->user->refresh();

    expect(Hash::check('NewPassword!23', $this->user->password))->toBeTrue();
    expect(Hash::check($this->password, $this->user->password))->toBeFalse();
});

it('updatePassword() rejects a wrong current password', function () {
    $page = new Profile;
    $page->passwordData = [
        'current_password' => 'WrongCurrentPassword',
        'password' => 'NewPassword!23',
        'password_confirmation' => 'NewPassword!23',
    ];
    $page->passwordForm->fill($page->passwordData);

    expect(fn () => $page->updatePassword())
        ->toThrow(ValidationException::class);

    $this->user->refresh();
    expect(Hash::check($this->password, $this->user->password))->toBeTrue();
});

it('updatePassword() rejects a mismatched confirmation', function () {
    $page = new Profile;
    $page->passwordData = [
        'current_password' => $this->password,
        'password' => 'NewPassword!23',
        'password_confirmation' => 'Different!23',
    ];
    $page->passwordForm->fill($page->passwordData);

    expect(fn () => $page->updatePassword())
        ->toThrow(ValidationException::class);
});

it('updatePassword() rejects a password shorter than 8 characters', function () {
    $page = new Profile;
    $page->passwordData = [
        'current_password' => $this->password,
        'password' => 'short',
        'password_confirmation' => 'short',
    ];
    $page->passwordForm->fill($page->passwordData);

    expect(fn () => $page->updatePassword())
        ->toThrow(ValidationException::class);
});

it('enableTwoFactor() generates an encrypted secret and 8 recovery codes', function () {
    $page = new Profile;
    expect($page->isTwoFactorEnabled())->toBeFalse();
    expect($page->isTwoFactorPending())->toBeFalse();

    $page->enableTwoFactor();

    $this->user->refresh();
    expect($this->user->two_factor_secret)->not->toBeNull();
    expect($this->user->two_factor_recovery_codes)->not->toBeNull();

    // The secret is decryptable + format-valid (Google2FA returns base32).
    $secret = decrypt($this->user->two_factor_secret);
    expect($secret)->toBeString();
    expect(strlen($secret))->toBeGreaterThanOrEqual(16);

    $codes = json_decode(decrypt($this->user->two_factor_recovery_codes), true);
    expect($codes)->toBeArray();
    expect(count($codes))->toBe(8);
});

it('confirmTwoFactor() verifies the OTP and stamps two_factor_confirmed_at', function () {
    $page = new Profile;
    $page->enableTwoFactor();
    $this->user->refresh();

    // Generate a valid OTP for the freshly created secret.
    $secret = decrypt($this->user->two_factor_secret);
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $page->twoFactorCode = $code;
    $page->confirmTwoFactor();

    $this->user->refresh();
    expect($this->user->two_factor_confirmed_at)->not->toBeNull();
    expect($page->isTwoFactorEnabled())->toBeTrue();
});

it('confirmTwoFactor() refuses an invalid OTP', function () {
    $page = new Profile;
    $page->enableTwoFactor();
    $this->user->refresh();

    $page->twoFactorCode = '000000';
    $page->confirmTwoFactor();

    $this->user->refresh();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
});

it('disableTwoFactor() clears the 2FA columns', function () {
    $page = new Profile;
    $page->enableTwoFactor();
    $this->user->refresh();

    expect($this->user->two_factor_secret)->not->toBeNull();

    $page->disableTwoFactor();

    $this->user->refresh();
    expect($this->user->two_factor_secret)->toBeNull();
    expect($this->user->two_factor_recovery_codes)->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
    expect($page->isTwoFactorEnabled())->toBeFalse();
});

it('regenerateRecoveryCodes() produces a fresh set distinct from the original', function () {
    $page = new Profile;
    $page->enableTwoFactor();
    $this->user->refresh();
    $original = json_decode(decrypt($this->user->two_factor_recovery_codes), true);

    $page->regenerateRecoveryCodes();
    $this->user->refresh();
    $regenerated = json_decode(decrypt($this->user->two_factor_recovery_codes), true);

    expect($regenerated)->toBeArray();
    expect(count($regenerated))->toBe(8);
    expect($regenerated)->not->toBe($original);
});

it('getRecoveryCodes() returns the persisted codes as an array', function () {
    $page = new Profile;
    $page->enableTwoFactor();

    $codes = $page->getRecoveryCodes();
    expect($codes)->toBeArray();
    expect(count($codes))->toBe(8);
    expect($codes[0])->toBeString();
});

it('getQrCodeSvg() returns a renderable QR string once 2FA is enrolling', function () {
    $page = new Profile;

    // Before enable: returns null.
    expect($page->getQrCodeSvg())->toBeNull();

    $page->enableTwoFactor();
    $this->user->refresh();

    $qr = $page->getQrCodeSvg();
    expect($qr)->toBeString();
    expect($qr)->not->toBe('');
});

it('Browser sessions section uses the database session driver and lists active sessions', function () {
    config(['session.driver' => 'database', 'session.connection' => null]);

    $page = new Profile;

    $currentId = Str::random(40);
    $otherId = Str::random(40);

    DB::table('sessions')->insert([
        [
            'id' => $currentId,
            'user_id' => $this->user->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Chrome/120',
            'payload' => 'x',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => $otherId,
            'user_id' => $this->user->getKey(),
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Mozilla/5.0 (iPhone) Safari',
            'payload' => 'y',
            'last_activity' => now()->subMinutes(10)->timestamp,
        ],
    ]);

    $sessions = $page->getSessions();
    expect($sessions)->toHaveCount(2);
    expect($sessions->first()->agent['browser'])->toBe('Chrome');
    expect($sessions->last()->agent['browser'])->toBe('Safari');
});

it('Browser sessions section returns an empty collection when session driver is not database', function () {
    config(['session.driver' => 'file']);

    $page = new Profile;
    expect($page->getSessions()->isEmpty())->toBeTrue();
});

it('logoutOtherBrowserSessions() deletes other-session rows when the current password is correct', function () {
    config(['session.driver' => 'database', 'session.connection' => null]);

    // Simulate two sessions for this user.
    DB::table('sessions')->insert([
        [
            'id' => 'currentsessionidaaaaaaaaaaaaaaaaaaaaaaaa',
            'user_id' => $this->user->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'browser/1',
            'payload' => 'x',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'othersessionidaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'user_id' => $this->user->getKey(),
            'ip_address' => '10.0.0.5',
            'user_agent' => 'browser/2',
            'payload' => 'y',
            'last_activity' => now()->timestamp,
        ],
    ]);

    request()->setLaravelSession(app('session.store'));
    request()->session()->setId('currentsessionidaaaaaaaaaaaaaaaaaaaaaaaa');

    dump('hasSession=' . var_export(request()->hasSession(), true));
    dump('session id=' . request()->session()->getId());

    $page = new Profile;
    $page->logoutPassword = $this->password;
    $page->logoutOtherBrowserSessions();

    expect(DB::table('sessions')->where('id', 'currentsessionidaaaaaaaaaaaaaaaaaaaaaaaa')->exists())->toBeTrue();
    expect(DB::table('sessions')->where('id', 'othersessionidaaaaaaaaaaaaaaaaaaaaaaaaaa')->exists())->toBeFalse();
});

it('logoutOtherBrowserSessions() refuses to log out other sessions with the wrong password', function () {
    config(['session.driver' => 'database', 'session.connection' => null]);

    DB::table('sessions')->insert([
        'id' => 'othersessionidbadaaaaaaaaaaaaaaaaaaaaaaa',
        'user_id' => $this->user->getKey(),
        'ip_address' => '10.0.0.5',
        'user_agent' => 'browser',
        'payload' => 'x',
        'last_activity' => now()->timestamp,
    ]);

    $page = new Profile;
    $page->logoutPassword = 'totally-wrong';

    expect(fn () => $page->logoutOtherBrowserSessions())
        ->toThrow(ValidationException::class);

    expect(DB::table('sessions')->where('id', 'othersessionidbadaaaaaaaaaaaaaaaaaaaaaaa')->exists())->toBeTrue();
});

it('renders the Profile blade with 2FA + browser-sessions + update-password sections', function () {
    $blade = file_get_contents(__DIR__ . '/../../resources/views/auth/profile.blade.php');

    expect($blade)->toContain('wire:submit="updatePassword"');
    expect($blade)->toContain('wire:click="enableTwoFactor"');
    expect($blade)->toContain('wire:click="disableTwoFactor"');
    expect($blade)->toContain('wire:click="confirmTwoFactor"');
    expect($blade)->toContain('wire:click="regenerateRecoveryCodes"');
    expect($blade)->toContain('wire:click="logoutOtherBrowserSessions"');
    expect($blade)->toContain('sections.two_factor_authentication');
    expect($blade)->toContain('sections.browser_sessions');
    expect($blade)->toContain('actions.log_out_other_sessions');
    expect($blade)->toContain('fields.current_password');
    expect($blade)->toContain('Recovery codes');
});

it('exposes translations for the new Profile sections + actions', function () {
    $labels = require __DIR__ . '/../../resources/lang/en/labels.php';

    expect($labels['sections']['update_password'])->toBe('Update password');
    expect($labels['sections']['two_factor_authentication'])->toBe('Two-factor authentication');
    expect($labels['sections']['browser_sessions'])->toBe('Browser sessions');
    expect($labels['actions']['enable_two_factor'])->toBe('Enable two-factor');
    expect($labels['actions']['disable_two_factor'])->toBe('Disable two-factor');
    expect($labels['actions']['log_out_other_sessions'])->toBe('Log out other browser sessions');
    expect($labels['fields']['current_password'])->toBe('Current password');
});
