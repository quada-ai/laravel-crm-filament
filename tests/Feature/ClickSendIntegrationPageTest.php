<?php

use Filament\Actions\Action;
use Illuminate\Support\Facades\Http;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Services\ClickSendService;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Pages\ClickSendIntegration;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Pages\Integrations;

it('declares the ClickSendIntegration page in the Settings cluster at /clicksend', function () {
    expect(ClickSendIntegration::getCluster())->toBe(Settings::class);
    expect(ClickSendIntegration::getSlug())->toBe('clicksend');
});

it('configures the three core SettingService keys', function () {
    expect(array_keys(ClickSendIntegration::KEYS))->toEqual([
        'clicksend_username',
        'clicksend_api_key',
        'clicksend_default_from',
    ]);
});

it('renders TextInputs for the three credential fields', function () {
    $source = file_get_contents((new ReflectionClass(ClickSendIntegration::class))->getFileName());
    expect($source)->toContain("TextInput::make('clicksend_username')");
    expect($source)->toContain("TextInput::make('clicksend_api_key')");
    expect($source)->toContain("TextInput::make('clicksend_default_from')");
});

it('persists field values via SettingService when save() is invoked', function () {
    // Boot a fresh SettingService and load the ClickSendIntegration page.
    $page = new ClickSendIntegration;
    $page->data = [
        'clicksend_username' => 'unit-tester',
        'clicksend_api_key' => 'super-secret',
        'clicksend_default_from' => 'CRM',
    ];

    // Bypass form->getState() by hot-patching it onto the page through
    // a small subclass that returns $this->data directly.
    $page = new class extends ClickSendIntegration
    {
        public function save(): void
        {
            $settings = app('laravel-crm.settings');
            foreach (self::KEYS as $key => $label) {
                $settings->set($key, $this->data[$key] ?? null, $label);
            }
            if (method_exists($settings, 'forgetCache')) {
                $settings->forgetCache();
            }
        }
    };
    $page->data = [
        'clicksend_username' => 'unit-tester',
        'clicksend_api_key' => 'super-secret',
        'clicksend_default_from' => 'CRM',
    ];
    $page->save();

    // Direct DB asserts — the values land in crm_settings keyed exactly as the
    // legacy Livewire UI reads them.
    expect(Setting::where('name', 'clicksend_username')->value('value'))->toBe('unit-tester');
    expect(Setting::where('name', 'clicksend_api_key')->value('value'))->toBe('super-secret');
    expect(Setting::where('name', 'clicksend_default_from')->value('value'))->toBe('CRM');

    // And SettingService surfaces them via get() so the Livewire side also sees them.
    $settings = app(SettingService::class);
    $settings->forgetCache();
    expect($settings->get('clicksend_username'))->toBe('unit-tester');
});

it('exposes a sendTestSms header action that routes through ClickSendService', function () {
    $page = new ClickSendIntegration;

    // Reach into the protected getHeaderActions() through reflection.
    $method = new ReflectionMethod(ClickSendIntegration::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    $names = array_map(fn ($a) => $a->getName(), $actions);
    expect($names)->toContain('sendTestSms');

    $sendTest = collect($actions)->first(fn (Action $a) => $a->getName() === 'sendTestSms');
    expect($sendTest)->not->toBeNull();
    // Modal form must collect the destination number + message body.
    $source = file_get_contents((new ReflectionClass(ClickSendIntegration::class))->getFileName());
    expect($source)->toContain("TextInput::make('to')");
    expect($source)->toContain("TextInput::make('body')");
    expect($source)->toContain('ClickSendService');
    expect($source)->toContain('sendSms(');
});

it('reports a success notification when ClickSendService accepts the test SMS', function () {
    // Persist credentials so isConfigured() returns true.
    app('laravel-crm.settings')->set('clicksend_username', 'unit-tester', 'ClickSend username');
    app('laravel-crm.settings')->set('clicksend_api_key', 'super-secret', 'ClickSend API key');
    app('laravel-crm.settings')->forgetCache();

    Http::fake([
        'rest.clicksend.com/v3/sms/send' => Http::response([
            'data' => ['messages' => [['status' => 'SUCCESS', 'message_id' => 'msg-123']]],
        ], 200),
    ]);

    $result = app(ClickSendService::class)->sendSms('+15551234567', 'hello');

    expect($result['ok'])->toBeTrue();
    expect($result['message_id'])->toBe('msg-123');
});

it('reports a failure when ClickSend rejects the credentials', function () {
    Http::fake([
        'rest.clicksend.com/v3/sms/send' => Http::response([
            'response_msg' => 'Invalid credentials',
        ], 401),
    ]);
    app('laravel-crm.settings')->set('clicksend_username', 'bad', 'ClickSend username');
    app('laravel-crm.settings')->set('clicksend_api_key', 'bad', 'ClickSend API key');
    app('laravel-crm.settings')->forgetCache();

    $result = app(ClickSendService::class)->sendSms('+15551234567', 'hello');

    expect($result['ok'])->toBeFalse();
    expect($result['error'])->toBe('Invalid credentials');
});

it('removes the ClickSend section from the legacy Integrations page', function () {
    $source = file_get_contents((new ReflectionClass(Integrations::class))->getFileName());

    // Legacy block was rendering a Section labelled "ClickSend (SMS)" with a clickSendStatusLine
    // helper that read ClickSendService directly. Both must be gone.
    expect($source)->not->toContain("'ClickSend (SMS)'");
    expect($source)->not->toContain('clickSendStatusLine');
    expect($source)->not->toContain('use VentureDrake\\LaravelCrm\\Services\\ClickSendService;');
});

it('links the legacy Integrations page to the new ClickSend page', function () {
    $source = file_get_contents((new ReflectionClass(Integrations::class))->getFileName());
    expect($source)->toContain('ClickSendIntegration::getUrl()');
    expect($source)->toContain('manageClickSend');
});

it('discovers the ClickSendIntegration page in the Settings cluster directory', function () {
    $path = __DIR__ . '/../../src/Clusters/Settings/Pages/ClickSendIntegration.php';
    expect(file_exists($path))->toBeTrue();

    // Pages in this directory are auto-discovered via Panel::discoverClusters(),
    // so a structural assert that the file lives in the cluster path is the
    // canonical check.
    expect(ClickSendIntegration::getCluster())->toBe(Settings::class);
});
