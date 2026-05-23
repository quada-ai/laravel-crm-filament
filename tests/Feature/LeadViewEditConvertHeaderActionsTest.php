<?php

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\EditLead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ViewLead;

function invokeLeadPageHeaderActions(string $page): array
{
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($page, 'getHeaderActions');
    $method->setAccessible(true);

    return $method->invoke($instance);
}

it('ViewLead::getHeaderActions includes the convertToDeal action alongside Edit', function () {
    $actions = invokeLeadPageHeaderActions(ViewLead::class);

    $names = array_map(fn ($a) => $a->getName(), $actions);
    expect($names)->toContain('convertToDeal');

    $convert = collect($actions)->firstWhere(fn ($a) => $a->getName() === 'convertToDeal');
    expect($convert)->toBeInstanceOf(Action::class);
    expect($convert->getColor())->toBe('success');
    expect($convert->isConfirmationRequired())->toBeTrue();

    $hasEdit = collect($actions)->contains(fn ($a) => $a instanceof EditAction);
    expect($hasEdit)->toBeTrue();
});

it('EditLead::getHeaderActions includes the convertToDeal action alongside View and Delete', function () {
    $actions = invokeLeadPageHeaderActions(EditLead::class);

    $names = array_map(fn ($a) => $a->getName(), $actions);
    expect($names)->toContain('convertToDeal');

    $convert = collect($actions)->firstWhere(fn ($a) => $a->getName() === 'convertToDeal');
    expect($convert)->toBeInstanceOf(Action::class);
    expect($convert->getColor())->toBe('success');
    expect($convert->isConfirmationRequired())->toBeTrue();

    $hasView = collect($actions)->contains(fn ($a) => $a instanceof ViewAction);
    $hasDelete = collect($actions)->contains(fn ($a) => $a instanceof DeleteAction);
    expect($hasView)->toBeTrue();
    expect($hasDelete)->toBeTrue();
});

it('ViewLead Convert action is hidden when the lead is already converted', function () {
    $actions = invokeLeadPageHeaderActions(ViewLead::class);
    $convert = collect($actions)->firstWhere(fn ($a) => $a->getName() === 'convertToDeal');

    $unconverted = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Still open',
    ]);
    $convert->record($unconverted);
    expect($convert->isVisible())->toBeTrue();

    $converted = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Already done',
        'converted_at' => now()->subDay(),
    ]);
    $convert->record($converted);
    expect($convert->isVisible())->toBeFalse();
});

it('EditLead Convert action is hidden when the lead is already converted', function () {
    $actions = invokeLeadPageHeaderActions(EditLead::class);
    $convert = collect($actions)->firstWhere(fn ($a) => $a->getName() === 'convertToDeal');

    $unconverted = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Still open',
    ]);
    $convert->record($unconverted);
    expect($convert->isVisible())->toBeTrue();

    $converted = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Already done',
        'converted_at' => now()->subDay(),
    ]);
    $convert->record($converted);
    expect($convert->isVisible())->toBeFalse();
});

it('Confirming the ViewLead Convert action persists a Deal and stamps converted_at', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Convert via view',
        'amount' => 2500,
        'currency' => 'USD',
    ]);

    $actions = invokeLeadPageHeaderActions(ViewLead::class);
    $convert = collect($actions)->firstWhere(fn ($a) => $a->getName() === 'convertToDeal');
    $convert->record($lead->fresh());
    $convert->call();

    expect($lead->fresh()->converted_at)->not->toBeNull();
    expect(Deal::query()->where('title', 'Convert via view')->exists())->toBeTrue();
});

it('Confirming the EditLead Convert action persists a Deal and stamps converted_at', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Convert via edit',
        'amount' => 4500,
        'currency' => 'USD',
    ]);

    $actions = invokeLeadPageHeaderActions(EditLead::class);
    $convert = collect($actions)->firstWhere(fn ($a) => $a->getName() === 'convertToDeal');
    $convert->record($lead->fresh());
    $convert->call();

    expect($lead->fresh()->converted_at)->not->toBeNull();
    expect(Deal::query()->where('title', 'Convert via edit')->exists())->toBeTrue();
});

it('ViewLead and EditLead source files contain LeadResource::convertAction()', function () {
    $viewSrc = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/Leads/Pages/ViewLead.php');
    $editSrc = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/Leads/Pages/EditLead.php');

    expect($viewSrc)->toContain('LeadResource::convertAction()');
    expect($editSrc)->toContain('LeadResource::convertAction()');
});
