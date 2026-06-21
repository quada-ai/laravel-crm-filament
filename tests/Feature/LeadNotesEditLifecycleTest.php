<?php

use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmNotesRelationManager;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

/**
 * Hot-patch subclass that bypasses $this->form->fill() / getState() (which require
 * a real Filament panel mount) and operates on $this->data directly. Mirrors the
 * pattern locked-in by LeadNotesCreateNoteTest.
 */
function leadNotesEditLifecycleRm(): CrmNotesRelationManager
{
    return new class extends CrmNotesRelationManager
    {
        public function editNote(int $id): void
        {
            $note = $this->getOwnerRecord()->notes()->whereKey($id)->first();

            if ($note === null) {
                return;
            }

            $this->editingId = (int) $note->id;

            $this->data = [
                'content' => $note->content,
                'noted_at' => $note->noted_at,
            ];
        }

        public function cancelEdit(): void
        {
            $this->editingId = null;

            $this->data = [
                'content' => null,
                'noted_at' => now(),
            ];
        }

        public function updateNote(): void
        {
            if ($this->editingId === null) {
                return;
            }

            $data = $this->data ?? [];

            $note = $this->getOwnerRecord()->notes()->whereKey($this->editingId)->first();

            if ($note === null) {
                $this->cancelEdit();

                return;
            }

            $note->update([
                'content' => $data['content'],
                'noted_at' => $data['noted_at'] ?? null,
                'user_updated_id' => auth()->id(),
            ]);

            self::logCrmActivity($this->getOwnerRecord(), $note);

            $this->editingId = null;
            $this->data = [
                'content' => null,
                'noted_at' => now(),
            ];

            Notification::make()
                ->title('Note updated')
                ->success()
                ->send();
        }
    };
}

it('declares a public ?int $editingId property defaulting to null', function () {
    $ref = new ReflectionClass(CrmNotesRelationManager::class);

    expect($ref->hasProperty('editingId'))->toBeTrue();

    $prop = $ref->getProperty('editingId');
    expect($prop->isPublic())->toBeTrue();
    expect($prop->getDeclaringClass()->getName())->toBe(CrmNotesRelationManager::class);

    $rm = $ref->newInstanceWithoutConstructor();
    expect($rm->editingId)->toBeNull();
});

it('declares public editNote(int), cancelEdit(), and updateNote() methods locally', function () {
    $ref = new ReflectionClass(CrmNotesRelationManager::class);

    foreach (['editNote', 'cancelEdit', 'updateNote'] as $methodName) {
        expect($ref->hasMethod($methodName))->toBeTrue();
        $method = $ref->getMethod($methodName);
        expect($method->isPublic())->toBeTrue();
        expect($method->getDeclaringClass()->getName())->toBe(CrmNotesRelationManager::class);
        expect($method->getReturnType()?->getName())->toBe('void');
    }

    $editNote = $ref->getMethod('editNote');
    expect($editNote->getNumberOfRequiredParameters())->toBe(1);
    $params = $editNote->getParameters();
    expect($params[0]->getName())->toBe('id');

    expect($ref->getMethod('cancelEdit')->getNumberOfRequiredParameters())->toBe(0);
    expect($ref->getMethod('updateNote')->getNumberOfRequiredParameters())->toBe(0);
});

it('editNote() hydrates the form data with the selected note content/noted_at and stamps editingId', function () {
    $rm = leadNotesEditLifecycleRm();

    $user = User::create([
        'name' => 'Note Editor',
        'email' => 'note-editor-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Lead with notes to edit',
    ]);

    $note = $lead->notes()->create([
        'content' => 'Original note body',
        'noted_at' => now()->subDay()->setSecond(0),
        'user_created_id' => $user->id,
    ]);

    $rm->ownerRecord = $lead->fresh();
    $rm->data = [];

    $rm->editNote((int) $note->id);

    expect($rm->editingId)->toBe((int) $note->id);
    expect($rm->data['content'])->toBe('Original note body');
    expect($rm->data['noted_at'])->not->toBeNull();
});

it('editNote() no-ops when the id does not belong to the owner record', function () {
    $rm = leadNotesEditLifecycleRm();

    $user = User::create([
        'name' => 'Note Editor B',
        'email' => 'note-editor-b-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $leadA = Lead::create(['external_id' => (string) Str::uuid(), 'title' => 'A']);
    $leadB = Lead::create(['external_id' => (string) Str::uuid(), 'title' => 'B']);

    // Note belongs to leadB, but the RM's owner is leadA.
    $note = $leadB->notes()->create([
        'content' => 'Belongs to B',
        'noted_at' => now(),
        'user_created_id' => $user->id,
    ]);

    $rm->ownerRecord = $leadA->fresh();
    $rm->data = ['content' => 'untouched', 'noted_at' => null];

    $rm->editNote((int) $note->id);

    expect($rm->editingId)->toBeNull();
    expect($rm->data['content'])->toBe('untouched');
});

it('cancelEdit() clears editingId and resets form state without persisting', function () {
    $rm = leadNotesEditLifecycleRm();

    $user = User::create([
        'name' => 'Note Editor C',
        'email' => 'note-editor-c-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $lead = Lead::create(['external_id' => (string) Str::uuid(), 'title' => 'Lead C']);
    $note = $lead->notes()->create([
        'content' => 'Pristine body',
        'noted_at' => now(),
        'user_created_id' => $user->id,
    ]);

    $rm->ownerRecord = $lead->fresh();
    $rm->data = [];

    $rm->editNote((int) $note->id);
    expect($rm->editingId)->not->toBeNull();
    expect($rm->data['content'])->toBe('Pristine body');

    $countBefore = Note::query()->count();

    $rm->cancelEdit();

    expect($rm->editingId)->toBeNull();
    expect($rm->data['content'])->toBeNull();
    expect(Note::query()->count())->toBe($countBefore);

    // The original note row was not mutated.
    expect($note->fresh()->content)->toBe('Pristine body');
});

it('updateNote() persists the edits, logs activity, clears edit state, and notifies', function () {
    $rm = leadNotesEditLifecycleRm();

    $user = User::create([
        'name' => 'Note Updater',
        'email' => 'note-updater-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Lead to update notes on',
    ]);

    $note = $lead->notes()->create([
        'content' => 'Initial body before edit',
        'noted_at' => now()->subDay()->setSecond(0),
        'user_created_id' => $user->id,
    ]);

    $rm->ownerRecord = $lead->fresh();
    $rm->editingId = (int) $note->id;
    $rm->data = [
        'content' => 'Edited body via inline form',
        'noted_at' => now()->setSecond(0),
    ];

    session()->forget('filament.notifications');

    $rm->updateNote();

    // Persisted via the owner relation (not orphaned).
    $reloaded = Note::find($note->id);
    expect($reloaded->content)->toBe('Edited body via inline form');
    expect($reloaded->noteable_type)->toBe($lead->getMorphClass());
    expect((int) $reloaded->noteable_id)->toBe($lead->id);
    expect((int) $reloaded->user_updated_id)->toBe($user->id);

    // Activity row written.
    expect(Activity::query()
        ->where('timelineable_type', $lead->getMorphClass())
        ->where('timelineable_id', $lead->id)
        ->where('recordable_type', $note->getMorphClass())
        ->where('recordable_id', $note->id)
        ->exists())->toBeTrue();

    // Edit state cleared.
    expect($rm->editingId)->toBeNull();
    expect($rm->data['content'])->toBeNull();

    // Notification fired.
    $notifications = session()->get('filament.notifications', []);
    $titles = array_map(fn ($n) => $n['title'] ?? null, $notifications);
    expect($titles)->toContain('Note updated');
});

it('updateNote() is a no-op when editingId is null', function () {
    $rm = leadNotesEditLifecycleRm();

    $user = User::create([
        'name' => 'Note Updater B',
        'email' => 'note-updater-b-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $lead = Lead::create(['external_id' => (string) Str::uuid(), 'title' => 'Lead null-id']);
    $note = $lead->notes()->create([
        'content' => 'untouched',
        'noted_at' => now(),
        'user_created_id' => $user->id,
    ]);

    $rm->ownerRecord = $lead->fresh();
    $rm->editingId = null;
    $rm->data = ['content' => 'replacement attempt', 'noted_at' => now()];

    $rm->updateNote();

    expect($note->fresh()->content)->toBe('untouched');
});

it('the real updateNote() method routes through form->getState(), owner relation, logCrmActivity, and Notification', function () {
    $src = file_get_contents(
        dirname(__DIR__, 2) . '/src/RelationManagers/CrmNotesRelationManager.php'
    );

    // editingId guard
    expect($src)->toContain('if ($this->editingId === null) {');

    // Validates via form->getState()
    expect($src)->toContain('$this->form->getState();');

    // Resolves the note via the owner relation (no orphaned writes)
    expect($src)->toContain('getOwnerRecord()->notes()->whereKey($this->editingId)');

    // Persists via update() with user_updated_id stamped
    expect($src)->toContain('$note->update([');
    expect($src)->toContain("'user_updated_id' => auth()->id()");

    // Logs activity
    expect($src)->toContain('static::logCrmActivity(');

    // Clears edit state
    expect($src)->toContain('$this->editingId = null;');

    // Notification fired as a success toast
    expect($src)->toContain("->title('Note updated')");
    expect($src)->toContain('->success()');
    expect($src)->toContain('->send()');
});

it('editNote() and cancelEdit() source bodies route through the form and owner relation', function () {
    $src = file_get_contents(
        dirname(__DIR__, 2) . '/src/RelationManagers/CrmNotesRelationManager.php'
    );

    // editNote looks up via the owner relation
    expect($src)->toContain('getOwnerRecord()->notes()->whereKey($id)');

    // editNote stamps editingId and hydrates the form
    expect($src)->toContain('$this->editingId = (int) $note->id;');
    expect($src)->toContain("'content' => \$note->content,");

    // cancelEdit resets both editingId and form state
    expect($src)->toContain('public function cancelEdit(): void');
    expect($src)->toContain('$this->form->fill([');
});
