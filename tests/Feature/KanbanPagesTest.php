<?php

use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\DealKanban;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\LeadKanban;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\QuoteKanban;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

dataset('kanbanPages', [
    'Lead' => [LeadKanban::class, LeadResource::class, 'moveLead'],
    'Deal' => [DealKanban::class, DealResource::class, 'moveDeal'],
    'Quote' => [QuoteKanban::class, QuoteResource::class, 'moveQuote'],
]);

it('declares each kanban page as a sub-resource page', function (string $page, string $resource) {
    expect((new ReflectionClass($page))->getStaticPropertyValue('resource'))->toBe($resource);
})->with('kanbanPages');

it('exposes the move method that Livewire calls on drop', function (string $page, string $resource, string $method) {
    expect(method_exists($page, $method))->toBeTrue();
    $reflection = new ReflectionMethod($page, $method);
    expect($reflection->isPublic())->toBeTrue();
    expect($reflection->getNumberOfRequiredParameters())->toBe(2);
})->with('kanbanPages');

it('registers the kanban route on each parent resource', function (string $page, string $resource) {
    expect($resource::getPages())->toHaveKey('kanban');
})->with('kanbanPages');
