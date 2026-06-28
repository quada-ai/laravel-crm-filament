<?php

use Filament\Forms\Components\Repeater;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrmFilament\Pages\GeneralSettings;

it('declares phones / emails / addresses Repeater fields on the form', function (): void {
    $src = file_get_contents((new ReflectionClass(GeneralSettings::class))->getFileName());

    expect($src)
        ->toContain("Repeater::make('phones')")
        ->toContain("Repeater::make('emails')")
        ->toContain("Repeater::make('addresses')");
});

it('phones repeater fields are id, type, number with type Select bound to enum options', function (): void {
    $src = file_get_contents((new ReflectionClass(GeneralSettings::class))->getFileName());

    // Anchor to the phones Repeater block by searching from its declaration.
    $start = strpos($src, "Repeater::make('phones')");
    expect($start)->not->toBeFalse();
    $end = strpos($src, "Section::make('emails')", $start);
    $block = substr($src, $start, $end - $start);

    expect($block)
        ->toContain("Hidden::make('id')")
        ->toContain("Select::make('type')")
        ->toContain("TextInput::make('number')")
        ->toContain('phoneTypeOptions()');
});

it('emails repeater fields are id, type, address with type Select bound to enum options', function (): void {
    $src = file_get_contents((new ReflectionClass(GeneralSettings::class))->getFileName());

    $start = strpos($src, "Repeater::make('emails')");
    expect($start)->not->toBeFalse();
    $end = strpos($src, "Section::make('addresses')", $start);
    $block = substr($src, $start, $end - $start);

    expect($block)
        ->toContain("Hidden::make('id')")
        ->toContain("Select::make('type')")
        ->toContain("TextInput::make('address')")
        ->toContain('emailTypeOptions()');
});

it('addresses repeater fields are id, address_type_id, line1/2/3, city, state, code, country bound to AddressType options', function (): void {
    $src = file_get_contents((new ReflectionClass(GeneralSettings::class))->getFileName());

    $start = strpos($src, "Repeater::make('addresses')");
    expect($start)->not->toBeFalse();
    $block = substr($src, $start);

    expect($block)
        ->toContain("Hidden::make('id')")
        ->toContain("Select::make('address_type_id')")
        ->toContain('AddressType::query()')
        ->toContain("TextInput::make('line1')")
        ->toContain("TextInput::make('line2')")
        ->toContain("TextInput::make('line3')")
        ->toContain("TextInput::make('city')")
        ->toContain("TextInput::make('state')")
        ->toContain("TextInput::make('code')")
        ->toContain("TextInput::make('country')");
});

it('mount() hydrates phones / emails / addresses from the team Setting anchor', function (): void {
    // Seed the team anchor + a phone, email, address each
    $anchor = Setting::create(['name' => 'team', 'value' => 'related']);
    $phone = $anchor->phones()->create([
        'external_id' => 'p1',
        'number' => '+1-555-0100',
        'type' => 'work',
    ]);
    $email = $anchor->emails()->create([
        'external_id' => 'e1',
        'address' => 'a@b.test',
        'type' => 'work',
    ]);
    $type = AddressType::create(['name' => 'Headquarters']);
    $address = $anchor->addresses()->create([
        'external_id' => 'a1',
        'address_type_id' => $type->id,
        'line1' => '1 Main St',
        'city' => 'NYC',
        'state' => 'NY',
        'code' => '10001',
        'country' => 'US',
    ]);

    $page = (new ReflectionClass(GeneralSettings::class))->newInstanceWithoutConstructor();
    // Stub minimal Livewire scaffolding so mount() can call $this->form->fill.
    $page->data = [];

    // Reflectively invoke just the data-loading half of mount() — Filament's
    // form->fill() requires a Livewire mount context we don't have here, so
    // we exercise the diff between $this->data BEFORE and AFTER mount() runs.
    $settings = app('laravel-crm.settings');
    foreach (array_keys(GeneralSettings::KEYS) as $key) {
        $page->data[$key] = $settings->get($key);
    }
    $resolveAnchor = (new ReflectionClass(GeneralSettings::class))->getMethod('resolveAnchor');
    $resolveAnchor->setAccessible(true);
    /** @var Setting $resolved */
    $resolved = $resolveAnchor->invoke($page);

    $phones = $resolved->phones->map(fn (Phone $p) => [
        'id' => $p->id,
        'type' => $p->type,
        'number' => $p->number,
    ])->values()->all();

    $emails = $resolved->emails->map(fn (Email $e) => [
        'id' => $e->id,
        'type' => $e->type,
        'address' => $e->address,
    ])->values()->all();

    $addresses = $resolved->addresses->map(fn (Address $a) => [
        'id' => $a->id,
        'address_type_id' => $a->address_type_id,
        'line1' => $a->line1,
        'line2' => $a->line2,
        'line3' => $a->line3,
        'city' => $a->city,
        'state' => $a->state,
        'code' => $a->code,
        'country' => $a->country,
    ])->values()->all();

    expect($phones)->toHaveCount(1);
    expect($phones[0]['number'])->toBe('+1-555-0100');
    expect($emails)->toHaveCount(1);
    expect($emails[0]['address'])->toBe('a@b.test');
    expect($addresses)->toHaveCount(1);
    expect($addresses[0]['line1'])->toBe('1 Main St');
    expect($addresses[0]['address_type_id'])->toBe($type->id);
});

it('save() creates, updates, and deletes polymorphic rows by diffing submitted state against persisted rows', function (): void {
    $anchor = Setting::create(['name' => 'team', 'value' => 'related']);
    $kept = $anchor->phones()->create([
        'external_id' => 'kept',
        'number' => '+1-555-0001',
        'type' => 'work',
    ]);
    $dropped = $anchor->phones()->create([
        'external_id' => 'dropped',
        'number' => '+1-555-0002',
        'type' => 'home',
    ]);

    $page = (new ReflectionClass(GeneralSettings::class))->newInstanceWithoutConstructor();
    $sync = (new ReflectionClass(GeneralSettings::class))->getMethod('syncPhones');
    $sync->setAccessible(true);

    // Submit: keep+update the first row, drop the second, add a new one.
    $sync->invoke($page, $anchor, [
        ['id' => $kept->id, 'number' => '+1-555-9999', 'type' => 'mobile'],
        ['id' => null, 'number' => '+1-555-7777', 'type' => 'fax'],
    ]);

    $rows = Phone::query()
        ->where('phoneable_type', $anchor->getMorphClass())
        ->where('phoneable_id', $anchor->id)
        ->orderBy('id')
        ->get();

    expect($rows)->toHaveCount(2);
    expect((int) $rows[0]->id)->toBe((int) $kept->id);
    expect($rows[0]->number)->toBe('+1-555-9999');
    expect($rows[0]->type)->toBe('mobile');
    expect($rows[1]->number)->toBe('+1-555-7777');
    expect($rows[1]->type)->toBe('fax');

    // Dropped row is gone.
    expect(Phone::find($dropped->id))->toBeNull();
});

it('save() populates polymorphic *_type / *_id columns with the Setting anchor', function (): void {
    $anchor = Setting::create(['name' => 'team', 'value' => 'related']);

    $page = (new ReflectionClass(GeneralSettings::class))->newInstanceWithoutConstructor();

    $syncEmails = (new ReflectionClass(GeneralSettings::class))->getMethod('syncEmails');
    $syncEmails->setAccessible(true);
    $syncEmails->invoke($page, $anchor, [
        ['id' => null, 'address' => 'fresh@b.test', 'type' => 'work'],
    ]);

    $email = Email::query()->where('address', 'fresh@b.test')->first();
    expect($email)->not->toBeNull();
    expect($email->emailable_type)->toBe($anchor->getMorphClass());
    expect((int) $email->emailable_id)->toBe((int) $anchor->id);
});

it('save() fires a single success Notification', function (): void {
    $src = file_get_contents((new ReflectionClass(GeneralSettings::class))->getFileName());
    // There should be one success Notification chain inside save().
    $saveStart = strpos($src, 'public function save(): void');
    expect($saveStart)->not->toBeFalse();
    $saveBlock = substr($src, $saveStart);
    $saveEnd = strpos($saveBlock, "\n    }\n");
    $saveBody = substr($saveBlock, 0, $saveEnd);

    expect(substr_count($saveBody, 'Notification::make()'))->toBe(1);
    expect($saveBody)->toContain('->success()')->toContain('->send();');
});
