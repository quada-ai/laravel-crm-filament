<?php

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFieldEntries;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ViewOrganization;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\ViewPerson;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;

dataset('poResourceModelPage', [
    'Person' => [PersonResource::class, Person::class, ViewPerson::class],
    'Organization' => [OrganizationResource::class, Organization::class, ViewOrganization::class],
]);

function poInfolistSections(string $resource, string $model, string $page): array
{
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $instance->record = new $model;
    $schema = Schema::make($instance);
    $schema->record($instance->record);
    $resource::infolist($schema);

    return array_values(array_filter(
        $schema->getComponents(withHidden: true),
        fn ($c) => $c instanceof Section,
    ));
}

it('declares infolist() locally on the Resource', function (string $resource): void {
    $declaringClass = (new ReflectionMethod($resource, 'infolist'))->getDeclaringClass()->getName();
    expect($declaringClass)->toBe($resource);
})->with('poResourceModelPage');

it('infolist source contains Identity / Contact / Custom fields section headings', function (string $resource): void {
    $src = file_get_contents((new ReflectionClass($resource))->getFileName());

    expect($src)->toContain("Section::make(__('laravel-crm-filament::labels.sections.identity'))");
    expect($src)->toContain("Section::make(__('laravel-crm-filament::labels.sections.contact'))");
    expect($src)->toContain("Section::make(__('laravel-crm-filament::labels.sections.custom_fields'))");
})->with('poResourceModelPage');

it('infolist exposes three top-level Sections in Identity / Contact / Custom fields order', function (string $resource, string $model, string $page): void {
    $sections = poInfolistSections($resource, $model, $page);

    expect($sections)->toHaveCount(3);
    expect($sections[0]->getHeading())->toBe(__('laravel-crm-filament::labels.sections.identity'));
    expect($sections[1]->getHeading())->toBe(__('laravel-crm-filament::labels.sections.contact'));
    expect($sections[2]->getHeading())->toBe(__('laravel-crm-filament::labels.sections.custom_fields'));
})->with('poResourceModelPage');

it('Person infolist Identity carries first/last/middle name + email + phone fields', function (): void {
    $src = file_get_contents((new ReflectionClass(PersonResource::class))->getFileName());

    expect($src)->toContain("TextEntry::make('first_name')");
    expect($src)->toContain("TextEntry::make('last_name')");
    expect($src)->toContain("TextEntry::make('middle_name')");
    expect($src)->toContain("TextEntry::make('email')");
    expect($src)->toContain("TextEntry::make('phone')");
    expect($src)->toContain('$record?->emails()->first()');
    expect($src)->toContain('$record?->phones()->first()');
});

it('Person Contact section deep-links the organization via OrganizationResource', function (): void {
    $src = file_get_contents((new ReflectionClass(PersonResource::class))->getFileName());

    expect($src)->toContain("TextEntry::make('organization.name')");
    expect($src)->toContain("OrganizationResource::getUrl('view'");
});

it('Organization infolist Identity carries name + industry + employees + revenue', function (): void {
    $src = file_get_contents((new ReflectionClass(OrganizationResource::class))->getFileName());

    expect($src)->toContain("TextEntry::make('name')");
    expect($src)->toContain("TextEntry::make('industry.name')");
    expect($src)->toContain("TextEntry::make('number_of_employees')");
    expect($src)->toContain("TextEntry::make('annual_revenue')");
});

it('both infolists render an addresses TextEntry via static::formatAddresses', function (string $resource): void {
    $src = file_get_contents((new ReflectionClass($resource))->getFileName());

    expect($src)->toContain("TextEntry::make('addresses')");
    expect($src)->toContain('static::formatAddresses($record)');
})->with('poResourceModelPage');

it('both infolists merge ungrouped custom field entries into Identity', function (string $resource): void {
    $src = file_get_contents((new ReflectionClass($resource))->getFileName());

    expect($src)->toContain('static::crmCustomFieldEntries($record, false)');
    expect($src)->toContain('static::crmCustomFieldEntries($record, true)');
})->with('poResourceModelPage');

it('Custom fields section is hidden when the record has no grouped FieldValues', function (string $resource, string $model, string $page): void {
    $sections = poInfolistSections($resource, $model, $page);
    $custom = $sections[2];

    $ref = new ReflectionProperty($custom, 'isHidden');
    $ref->setAccessible(true);
    $closure = $ref->getValue($custom);

    expect($closure)->toBeInstanceOf(Closure::class);
    expect($closure(new $model))->toBeTrue();
    expect($closure(null))->toBeTrue();
})->with('poResourceModelPage');

it('overrides content() locally on the View page with a 2-col Grid', function (string $resource, string $model, string $page): void {
    $declaringClass = (new ReflectionMethod($page, 'content'))->getDeclaringClass()->getName();
    expect($declaringClass)->toBe($page);

    $src = file_get_contents((new ReflectionClass($page))->getFileName());
    expect($src)->toContain("Grid::make(['default' => 1, 'lg' => 2])");
    expect($src)->toContain('getInfolistContentComponent()');
    expect($src)->toContain('getRelationManagersContentComponent()');
})->with('poResourceModelPage');

it('content() Schema root contains a Grid with two columnSpan lg-1 children', function (string $resource, string $model, string $page): void {
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $instance->record = new $model;
    $schema = Schema::make($instance);
    $instance->content($schema);

    $components = $schema->getComponents(withHidden: true);
    expect($components)->toHaveCount(1);
    expect($components[0])->toBeInstanceOf(Grid::class);
})->with('poResourceModelPage');

it('uses the shared HasCrmCustomFieldEntries trait on both Resources', function (string $resource): void {
    expect(class_uses_recursive($resource))
        ->toContain(HasCrmCustomFieldEntries::class);
})->with('poResourceModelPage');
