<?php

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Users\Pages\ViewUser;
use VentureDrake\LaravelCrmFilament\Resources\Users\UserResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Eva Steinberg',
        'email' => 'eva.steinberg' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('ViewUser overrides getTitle() to return the bare record title (no "View " prefix)', function () {
    $page = (new ReflectionClass(ViewUser::class))->newInstanceWithoutConstructor();
    $rec = new ReflectionProperty($page, 'record');
    $rec->setAccessible(true);
    $rec->setValue($page, $this->user);

    expect($page->getTitle())->toBe('Eva Steinberg');
    expect((string) $page->getTitle())->not->toStartWith('View ');
});

it('ViewUser header actions render Back to users FIRST, then Edit, then Delete', function () {
    $page = (new ReflectionClass(ViewUser::class))->newInstanceWithoutConstructor();
    $method = (new ReflectionClass($page))->getMethod('getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    expect($actions)->toHaveCount(3);
    expect($actions[0])->toBeInstanceOf(Action::class);
    expect($actions[0]->getName())->toBe('backToIndex');
    expect($actions[1])->toBeInstanceOf(EditAction::class);
    expect($actions[2])->toBeInstanceOf(DeleteAction::class);
});

it('UserResource::backToIndexAction() routes to the index URL with the back arrow icon', function () {
    $action = UserResource::backToIndexAction();
    expect($action->getName())->toBe('backToIndex');
    expect($action->getColor())->toBe('gray');
    expect($action->getIcon())->toBe('heroicon-o-arrow-left');
    expect($action->getUrl())->toBe(UserResource::getUrl('index'));
});

it('UserResource::infolist() Details section exposes the 7 parity fields in core CRM order', function () {
    $schema = Schema::make((new ReflectionClass(ViewUser::class))->newInstanceWithoutConstructor());
    UserResource::infolist($schema);

    $sections = collect($schema->getComponents(withHidden: true))
        ->filter(fn ($c) => $c instanceof Section)
        ->values();

    expect($sections)->toHaveCount(1);

    $detailsChildren = $sections[0]->getChildComponents();
    $names = array_values(array_map(
        fn (TextEntry $entry) => $entry->getName(),
        array_filter($detailsChildren, fn ($c) => $c instanceof TextEntry)
    ));

    expect($names)->toBe([
        'name',
        'email',
        'email_verified_at',
        'crm_access',
        'role',
        'created_at',
        'last_online_at',
    ]);
});
