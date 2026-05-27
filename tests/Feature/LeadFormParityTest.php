<?php

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\LeadDealContactSection;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\CreateLead;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Lead Form Parity Tester',
        'email' => 'lead-form-parity-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('LeadResource form is a 2-column Grid with Contact, Organization, and Details sections', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Resources/Leads/LeadResource.php');
    expect($source)->toContain("Grid::make(['default' => 1, 'lg' => 2])");
    expect($source)->toContain('LeadDealContactSection::contactColumn()');
    expect($source)->toContain('LeadDealContactSection::organizationColumn()');
    expect($source)->toContain("Section::make(__('laravel-crm-filament::labels.sections.details'))");
});

/**
 * Extract the first child Component from a Section without needing a parent
 * container. Section::getChildComponents() routes through getChildSchema()
 * which requires the section to be mounted into a Schema; in pure unit
 * tests we read the protected $childComponents array directly via
 * Reflection.
 */
function leadFormFirstChildOf(Section $section): Component
{
    $ref = new ReflectionProperty($section, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($section);
    $defaultGroup = $children['default'] ?? $children;
    if (is_array($defaultGroup) && isset($defaultGroup[0])) {
        return $defaultGroup[0];
    }

    return $defaultGroup;
}

it('Contact column is a Section with a person_id Select that exposes a createOptionForm', function () {
    $section = LeadDealContactSection::contactColumn();

    expect($section)->toBeInstanceOf(Section::class);

    /** @var Select $select */
    $select = leadFormFirstChildOf($section);
    expect($select)->toBeInstanceOf(Select::class);
    expect($select->getName())->toBe('person_id');
    expect($select->hasCreateOptionActionFormSchema())->toBeTrue();
    expect($select->getCreateOptionUsing())->toBeInstanceOf(Closure::class);
});

it('Organization column is a Section with an organization_id Select that exposes a createOptionForm', function () {
    $section = LeadDealContactSection::organizationColumn();

    expect($section)->toBeInstanceOf(Section::class);

    /** @var Select $select */
    $select = leadFormFirstChildOf($section);
    expect($select->getName())->toBe('organization_id');
    expect($select->hasCreateOptionActionFormSchema())->toBeTrue();
    expect($select->getCreateOptionUsing())->toBeInstanceOf(Closure::class);
});

it('Contact createOptionUsing creates a Person via PersonService::createFromRelated and returns its id', function () {
    $section = LeadDealContactSection::contactColumn();
    $select = leadFormFirstChildOf($section);
    /** @var Closure $createOption */
    $createOption = $select->getCreateOptionUsing();

    $id = $createOption([
        'first_name' => 'Sasha',
        'last_name' => 'Banks',
        'phone' => '555-0100',
        'phone_type' => 'mobile',
        'email' => 'sasha@example.com',
        'email_type' => 'work',
    ]);

    expect($id)->toBeInt();
    $person = Person::find($id);
    expect($person)->not->toBeNull();
    expect($person->first_name)->toBe('Sasha');
    expect($person->last_name)->toBe('Banks');
    expect($person->phones()->count())->toBe(1);
    expect($person->phones->first()->number)->toBe('555-0100');
    expect($person->emails()->count())->toBe(1);
    expect($person->emails->first()->address)->toBe('sasha@example.com');
});

it('Organization createOptionUsing creates an Organization with an address row', function () {
    $section = LeadDealContactSection::organizationColumn();
    $select = leadFormFirstChildOf($section);
    /** @var Closure $createOption */
    $createOption = $select->getCreateOptionUsing();

    $id = $createOption([
        'name' => 'Acme Industries',
        'line1' => '1 Wharf Street',
        'city' => 'Sydney',
        'state' => 'NSW',
        'code' => '2000',
        'country' => 'AU',
    ]);

    expect($id)->toBeInt();
    $org = Organization::find($id);
    expect($org)->not->toBeNull();
    expect($org->name)->toBe('Acme Industries');
    expect($org->addresses()->count())->toBe(1);
    $address = $org->addresses->first();
    expect($address->line1)->toBe('1 Wharf Street');
    expect($address->suburb)->toBe('Sydney');
});

it('CreateLead resolves person_id and organization_id and passes the models to LeadService', function () {
    $person = Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Existing',
        'last_name' => 'Contact',
    ]);
    $organization = Organization::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Existing Org',
    ]);

    livewire(CreateLead::class)
        ->fillForm([
            'title' => 'My new lead',
            'description' => 'desc',
            'currency' => 'USD',
            'person_id' => $person->getKey(),
            'organization_id' => $organization->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $lead = Lead::query()->where('title', 'My new lead')->first();
    expect($lead)->not->toBeNull();
    expect($lead->person_id)->toBe($person->getKey());
    expect($lead->organization_id)->toBe($organization->getKey());
});
