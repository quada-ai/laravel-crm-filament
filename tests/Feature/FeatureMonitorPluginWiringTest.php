<?php

use Filament\Panel;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Features\FeatureResource;
use VentureDrake\LaravelCrmFilament\Resources\FeatureStatuses\FeatureStatusResource;
use VentureDrake\LaravelCrmFilament\Resources\Monitors\MonitorResource;

// AC: Register FeatureResource and MonitorResource in LaravelCrmPlugin::register()
// AFTER all other module-gated resources so a failure in one doesn't break the
// test panel. The "Roadmap + monitoring resources are registered LAST" comment
// in src/LaravelCrmPlugin.php marks the canonical position.
it('registers FeatureResource and MonitorResource as the last module-gated resources in register()', function () {
    $source = file_get_contents((new ReflectionClass(LaravelCrmPlugin::class))->getFileName());

    // The Roadmap+monitoring comment header is the structural marker.
    expect($source)->toContain('// Roadmap + monitoring resources are registered LAST');

    // Both registrations live AFTER the xero-gated block.
    $xeroPos = strrpos($source, "isModuleEnabled('xero')");
    $featurePos = strrpos($source, "isModuleEnabled('features')");
    $monitoringPos = strrpos($source, "isModuleEnabled('monitoring')");

    expect($xeroPos)->toBeGreaterThan(0);
    expect($featurePos)->toBeGreaterThan($xeroPos);
    expect($monitoringPos)->toBeGreaterThan($xeroPos);

    // And BEFORE the brand/panel `->resources($resources)` call (still inside the array build).
    $resourcesCallPos = strpos($source, '$panel->resources($resources);');
    expect($featurePos)->toBeLessThan($resourcesCallPos);
    expect($monitoringPos)->toBeLessThan($resourcesCallPos);
});

it('exposes a fluent withFeatures(bool) setter that toggles the features module override', function () {
    $plugin = LaravelCrmPlugin::make();

    // Chainable shape matches withChat/withCustomers.
    expect($plugin->withFeatures(true))->toBe($plugin);
    expect($plugin->isModuleEnabled('features'))->toBeTrue();

    expect($plugin->withFeatures(false))->toBe($plugin);
    expect($plugin->isModuleEnabled('features'))->toBeFalse();

    // Default arg is true.
    $plugin2 = LaravelCrmPlugin::make()->withFeatures();
    expect($plugin2->isModuleEnabled('features'))->toBeTrue();
});

it('exposes a fluent withMonitoring(bool) setter that toggles the monitoring module override', function () {
    $plugin = LaravelCrmPlugin::make();

    expect($plugin->withMonitoring(true))->toBe($plugin);
    expect($plugin->isModuleEnabled('monitoring'))->toBeTrue();

    expect($plugin->withMonitoring(false))->toBe($plugin);
    expect($plugin->isModuleEnabled('monitoring'))->toBeFalse();

    $plugin2 = LaravelCrmPlugin::make()->withMonitoring();
    expect($plugin2->isModuleEnabled('monitoring'))->toBeTrue();
});

it('registers FeatureResource only when the features module is enabled', function () {
    $plugin = LaravelCrmPlugin::make()->withFeatures(true);
    $panel = Panel::make()->id('us008-features-on')->default();
    $plugin->register($panel);

    expect($panel->getResources())->toContain(FeatureResource::class);

    $plugin = LaravelCrmPlugin::make()->withFeatures(false);
    $panel = Panel::make()->id('us008-features-off')->default();
    $plugin->register($panel);

    expect($panel->getResources())->not->toContain(FeatureResource::class);
});

it('registers MonitorResource only when the monitoring module is enabled', function () {
    $plugin = LaravelCrmPlugin::make()->withMonitoring(true);
    $panel = Panel::make()->id('us008-monitoring-on')->default();
    $plugin->register($panel);

    expect($panel->getResources())->toContain(MonitorResource::class);

    $plugin = LaravelCrmPlugin::make()->withMonitoring(false);
    $panel = Panel::make()->id('us008-monitoring-off')->default();
    $plugin->register($panel);

    expect($panel->getResources())->not->toContain(MonitorResource::class);
});

it('always registers FeatureStatusResource regardless of the features module flag', function () {
    // With features OFF, FeatureStatusResource is still on the panel
    // (matches the Settings cluster precedent — admin lookup tables stay available).
    $plugin = LaravelCrmPlugin::make()->withFeatures(false);
    $panel = Panel::make()->id('us008-feature-statuses-off')->default();
    $plugin->register($panel);

    expect($panel->getResources())->toContain(FeatureStatusResource::class);

    // And with features ON.
    $plugin = LaravelCrmPlugin::make()->withFeatures(true);
    $panel = Panel::make()->id('us008-feature-statuses-on')->default();
    $plugin->register($panel);

    expect($panel->getResources())->toContain(FeatureStatusResource::class);
});

it('translates AC-named action keys in en/fr/es', function () {
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . "/resources/lang/{$locale}/labels.php";

        // AC names these five action keys explicitly.
        expect($labels['actions']['back_to_features'])->toBeString()->not->toBe('');
        expect($labels['actions']['back_to_monitors'])->toBeString()->not->toBe('');
        expect($labels['actions']['run_check_now'])->toBeString()->not->toBe('');
        expect($labels['actions']['add_status'])->toBeString()->not->toBe('');
        expect($labels['actions']['add_comment'])->toBeString()->not->toBe('');
    }
});

it('translates field and section keys referenced by Feature and Monitor infolists in en/fr/es', function () {
    $root = dirname(__DIR__, 2);

    // Subset of field/section keys the new infolists reach for. Pulled from a
    // grep of FeatureResource + MonitorResource source.
    $expectedFields = [
        'feature_status', 'is_public', 'votes', 'comments', 'views', 'submitted_by',
        'last_status', 'last_response_time', 'last_status_code', 'last_checked_at',
        'last_status_changed_at', 'down_since_at', 'ssl_status', 'ssl_issuer',
        'ssl_expires_at', 'ssl_last_checked_at',
    ];
    $expectedSections = [
        'details', 'custom_fields', 'monitor_settings', 'ssl', 'status',
    ];

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . "/resources/lang/{$locale}/labels.php";

        foreach ($expectedFields as $key) {
            expect($labels['fields'][$key] ?? null)
                ->toBeString()
                ->not->toBe('')
                ->and($labels['fields'][$key])->toBeString();
        }

        foreach ($expectedSections as $key) {
            expect($labels['sections'][$key] ?? null)
                ->toBeString()
                ->not->toBe('')
                ->and($labels['sections'][$key])->toBeString();
        }
    }
});
