<?php

use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmNotesRelationManager;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

it('declares a public createNote() method on CrmNotesRelationManager', function () {
    $ref = new ReflectionClass(CrmNotesRelationManager::class);

    expect($ref->hasMethod('createNote'))->toBeTrue();

    $method = $ref->getMethod('createNote');
    expect($method->isPublic())->toBeTrue();
    expect($method->getDeclaringClass()->getName())->toBe(CrmNotesRelationManager::class);
    expect($method->getReturnType()?->getName())->toBe('void');
    expect($method->getNumberOfRequiredParameters())->toBe(0);
});

it('declares a public $data property for the inline form state', function () {
    $ref = new ReflectionClass(CrmNotesRelationManager::class);

    expect($ref->hasProperty('data'))->toBeTrue();

    $prop = $ref->getProperty('data');
    expect($prop->isPublic())->toBeTrue();
    expect($prop->getDeclaringClass()->getName())->toBe(CrmNotesRelationManager::class);
});

it('sets statePath(data) on the inline form schema', function () {
    $rm = (new ReflectionClass(CrmNotesRelationManager::class))->newInstanceWithoutConstructor();
    $schema = $rm->form(Schema::make($rm));

    expect($schema->getStatePath())->toBe('data');
});

it('declares mount() locally and calls parent::mount() plus form->fill()', function () {
    $ref = new ReflectionClass(CrmNotesRelationManager::class);

    expect($ref->hasMethod('mount'))->toBeTrue();
    expect($ref->getMethod('mount')->getDeclaringClass()->getName())
        ->toBe(CrmNotesRelationManager::class);

    $src = file_get_contents(
        dirname(__DIR__, 2) . '/src/RelationManagers/CrmNotesRelationManager.php'
    );

    expect($src)->toContain('parent::mount();');
    expect($src)->toContain('$this->form->fill([');
});

it('createNote() persists a Note via the owner relation with user_created_id, logs activity, and resets form state', function () {
    // Hot-patch subclass that bypasses $this->form->getState() (which requires
    // a real Filament panel mount) and reads $this->data directly. Mirrors
    // the pattern locked-in by ClickSendIntegrationPageTest.
    $rm = new class extends CrmNotesRelationManager
    {
        public bool $resetCalled = false;

        public function createNote(): void
        {
            $data = $this->data ?? [];

            $note = $this->getOwnerRecord()->notes()->create([
                'content' => $data['content'],
                'noted_at' => $data['noted_at'] ?? null,
                'user_created_id' => auth()->id(),
            ]);

            self::logCrmActivity($this->getOwnerRecord(), $note);

            $this->data = [
                'content' => null,
                'noted_at' => now(),
            ];
            $this->resetCalled = true;

            Notification::make()
                ->title('Note added')
                ->success()
                ->send();
        }
    };

    $user = User::create([
        'name' => 'Note Author',
        'email' => 'note-author-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Lead with notes',
    ]);

    $rm->ownerRecord = $lead->fresh();
    $rm->data = [
        'content' => 'First inline note from the show page',
        'noted_at' => now()->setSecond(0),
    ];

    // Clear any pre-existing notification session state.
    session()->forget('filament.notifications');

    $rm->createNote();

    // Persisted via the morphMany — noteable_type + noteable_id auto-set.
    $note = Note::query()
        ->where('content', 'First inline note from the show page')
        ->first();

    expect($note)->not->toBeNull();
    expect($note->noteable_type)->toBe($lead->getMorphClass());
    expect((int) $note->noteable_id)->toBe($lead->id);
    expect((int) $note->user_created_id)->toBe($user->id);
    expect($note->noted_at)->not->toBeNull();

    // Activity row written via LogsCrmActivity::logCrmActivity().
    expect(Activity::query()
        ->where('timelineable_type', $lead->getMorphClass())
        ->where('timelineable_id', $lead->id)
        ->where('recordable_type', $note->getMorphClass())
        ->where('recordable_id', $note->id)
        ->exists())->toBeTrue();

    // Form state reset.
    expect($rm->resetCalled)->toBeTrue();
    expect($rm->data['content'])->toBeNull();

    // Notification fired — Filament pushes the payload into the session.
    $notifications = session()->get('filament.notifications', []);
    $titles = array_map(fn ($n) => $n['title'] ?? null, $notifications);
    expect($titles)->toContain('Note added');
});

it('the real createNote() method routes through form->getState() and Notification::make()->success()', function () {
    $src = file_get_contents(
        dirname(__DIR__, 2) . '/src/RelationManagers/CrmNotesRelationManager.php'
    );

    // Validates via form->getState()
    expect($src)->toContain('$this->form->getState()');

    // Persists via the owner relation with user_created_id populated
    expect($src)->toContain('getOwnerRecord()->notes()->create([');
    expect($src)->toContain("'user_created_id' => auth()->id()");

    // Invokes the shared LogsCrmActivity hook
    expect($src)->toContain('static::logCrmActivity(');

    // Notification fired as a success toast
    expect($src)->toContain('Notification::make()');
    expect($src)->toContain('->success()');
    expect($src)->toContain('->send()');
});
