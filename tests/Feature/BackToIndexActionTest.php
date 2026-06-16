<?php

use Filament\Actions\Action;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

dataset('back_to_index_resources', [
    'Lead' => [LeadResource::class, 'leads'],
    'Quote' => [QuoteResource::class, 'quotes'],
    'Deal' => [DealResource::class, 'deals'],
]);

it('exposes backToIndexAction as a public static returning Action', function (string $resource): void {
    $reflection = new ReflectionMethod($resource, 'backToIndexAction');

    expect($reflection->isPublic())->toBeTrue();
    expect($reflection->isStatic())->toBeTrue();
    expect($reflection->getNumberOfParameters())->toBe(0);
    expect((string) $reflection->getReturnType())->toBe(Action::class);
})->with([
    'Lead' => [LeadResource::class],
    'Quote' => [QuoteResource::class],
    'Deal' => [DealResource::class],
]);

it('returns an Action named backToIndex with gray color and arrow-left icon pointing at the resource index', function (string $resource, string $entity): void {
    /** @var Action $action */
    $action = $resource::backToIndexAction();

    expect($action)->toBeInstanceOf(Action::class);
    expect($action->getName())->toBe('backToIndex');
    expect($action->getColor())->toBe('gray');
    expect($action->getIcon())->toBe('heroicon-o-arrow-left');
    expect($action->getUrl())->toBe($resource::getUrl('index'));
})->with('back_to_index_resources');

it('uses the per-entity back_to_<entity> translation key', function (string $resource, string $entity): void {
    $key = "laravel-crm-filament::labels.actions.back_to_{$entity}";

    expect($resource::backToIndexAction()->getLabel())->toBe(__($key));
})->with('back_to_index_resources');
