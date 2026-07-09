<?php

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Livewire\Attributes\Url;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Pages\ActivityFeed;
use VentureDrake\LaravelCrmFilament\Pages\CalendarPage;

it('extends Filament\Pages\Page and declares the activities slug', function () {
    expect(is_subclass_of(ActivityFeed::class, Page::class))->toBeTrue();
    expect(ActivityFeed::getSlug())->toBe('activities');
});

it('declares the heroicon-o-bolt navigation icon and Activity title', function () {
    $iconProp = new ReflectionProperty(ActivityFeed::class, 'navigationIcon');
    expect($iconProp->getDefaultValue())->toBe('heroicon-o-bolt');

    $titleProp = new ReflectionProperty(ActivityFeed::class, 'title');
    expect($titleProp->getDefaultValue())->toBe('Activity');
});

it('points $view at laravel-crm-filament::activity-feed', function () {
    $viewProp = new ReflectionProperty(ActivityFeed::class, 'view');
    expect($viewProp->getDefaultValue())->toBe('laravel-crm-filament::activity-feed');
});

it('getNavigationGroup() returns Activity by default via the plugin helper', function () {
    $panel = Panel::make()->id('us002-activity-feed-nav')->default();
    $plugin = LaravelCrmPlugin::make();
    $panel->plugin($plugin);
    $plugin->register($panel);
    Filament::setCurrentPanel($panel);

    expect(ActivityFeed::getNavigationGroup())->toBe('Activity');
});

it('declares public string $scope defaulting to mine with #[Url]', function () {
    $prop = new ReflectionProperty(ActivityFeed::class, 'scope');
    expect($prop->isPublic())->toBeTrue();
    expect($prop->getType()?->getName())->toBe('string');
    expect($prop->getDefaultValue())->toBe('mine');
    $attrs = $prop->getAttributes(Url::class);
    expect($attrs)->not->toBeEmpty();
});

it('declares public string $tab defaulting to all with #[Url]', function () {
    $prop = new ReflectionProperty(ActivityFeed::class, 'tab');
    expect($prop->isPublic())->toBeTrue();
    expect($prop->getType()?->getName())->toBe('string');
    expect($prop->getDefaultValue())->toBe('all');
    $attrs = $prop->getAttributes(Url::class);
    expect($attrs)->not->toBeEmpty();
});

it('exposes setScope(string) and setTab(string) as public one-arg methods', function () {
    $setScope = new ReflectionMethod(ActivityFeed::class, 'setScope');
    expect($setScope->isPublic())->toBeTrue();
    expect($setScope->getNumberOfRequiredParameters())->toBe(1);
    expect($setScope->getParameters()[0]->getType()?->getName())->toBe('string');

    $setTab = new ReflectionMethod(ActivityFeed::class, 'setTab');
    expect($setTab->isPublic())->toBeTrue();
    expect($setTab->getNumberOfRequiredParameters())->toBe(1);
    expect($setTab->getParameters()[0]->getType()?->getName())->toBe('string');
});

it('setScope() and setTab() mutate the corresponding property', function () {
    $page = (new ReflectionClass(ActivityFeed::class))->newInstanceWithoutConstructor();
    $page->setScope('all');
    expect($page->scope)->toBe('all');
    $page->setTab('notes');
    expect($page->tab)->toBe('notes');
});

it('is registered on the panel alongside CalendarPage', function () {
    $panel = Panel::make()->id('us002-activity-feed-panel')->default();
    LaravelCrmPlugin::make()->register($panel);

    $pages = $panel->getPages();
    expect($pages)->toContain(ActivityFeed::class);
    expect($pages)->toContain(CalendarPage::class);
});

it('LaravelCrmPlugin source imports ActivityFeed and appends it to $pages', function () {
    $src = file_get_contents((new ReflectionClass(LaravelCrmPlugin::class))->getFileName());
    expect($src)->toContain('use VentureDrake\\LaravelCrmFilament\\Pages\\ActivityFeed;');
    expect($src)->toContain('ActivityFeed::class,');
});
