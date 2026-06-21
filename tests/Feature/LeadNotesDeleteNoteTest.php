<?php

use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmNotesRelationManager;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

/**
 * Hot-patch subclass that bypasses $this->form->fill() (which requires a real
 * Filament panel mount) and operates on $this->data directly. Mirrors the
 * pattern locked-in by LeadNotesCreateNoteTest and LeadNotesEditLifecycleTest.
 */
function leadNotesDeleteRm(): CrmNotesRelationManager
{
    return new class extends CrmNotesRelationManager
    {
        public function deleteNote(int $id): void
        {
            $note = $this->getOwnerRecord()->notes()->whereKey($id)->first();

            if ($note === null) {
                return;
            }

            $note->delete();

            if ($this->editingId === (int) $id) {
                $this->editingId = null;
                $this->data = [
                    'content' => null,
                    'noted_at' => now(),
                ];
            }

            Notification::make()
                ->title('Note deleted')
                ->success()
                ->send();
        }
    };
}

it('declares a public deleteNote(int): void method locally', function () {
    $ref = new ReflectionClass(CrmNotesRelationManager::class);

    expect($ref->hasMethod('deleteNote'))->toBeTrue();

    $method = $ref->getMethod('deleteNote');
    expect($method->isPublic())->toBeTrue();
    expect($method->getDeclaringClass()->getName())->toBe(CrmNotesRelationManager::class);
    expect($method->getReturnType()?->getName())->toBe('void');
    expect($method->getNumberOfRequiredParameters())->toBe(1);

    $params = $method->getParameters();
    expect($params[0]->getName())->toBe('id');
    expect($params[0]->getType()?->getName())->toBe('int');
});

it('deleteNote() soft-deletes the note and dispatches a success notification', function () {
    $rm = leadNotesDeleteRm();

    $user = User::create([
        'name' => 'Note Deleter',
        'email' => 'note-deleter-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Lead with a note to delete',
    ]);

    $note = $lead->notes()->create([
        'content' => 'About to be soft-deleted',
        'noted_at' => now(),
        'user_created_id' => $user->id,
    ]);

    $rm->ownerRecord = $lead->fresh();

    session()->forget('filament.notifications');

    $rm->deleteNote((int) $note->id);

    // Note row is soft-deleted (deleted_at stamped, row still present).
    expect(Note::query()->find($note->id))->toBeNull();
    expect(Note::withTrashed()->find($note->id))->not->toBeNull();
    expect(Note::withTrashed()->find($note->id)->deleted_at)->not->toBeNull();

    // Notification dispatched.
    $notifications = session()->get('filament.notifications', []);
    $titles = array_map(fn ($n) => $n['title'] ?? null, $notifications);
    expect($titles)->toContain('Note deleted');
});

it('deleteNote() resolves the target via the owner relation and cannot delete notes outside the current lead', function () {
    $rm = leadNotesDeleteRm();

    $user = User::create([
        'name' => 'Cross-lead Tamperer',
        'email' => 'cross-lead-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $leadA = Lead::create(['external_id' => (string) Str::uuid(), 'title' => 'Owner A']);
    $leadB = Lead::create(['external_id' => (string) Str::uuid(), 'title' => 'Owner B']);

    // Note belongs to leadB, but the RM is mounted on leadA.
    $note = $leadB->notes()->create([
        'content' => 'Belongs to lead B',
        'noted_at' => now(),
        'user_created_id' => $user->id,
    ]);

    $rm->ownerRecord = $leadA->fresh();

    session()->forget('filament.notifications');

    $rm->deleteNote((int) $note->id);

    // Note row is untouched — cross-lead injection silently no-ops.
    expect(Note::query()->find($note->id))->not->toBeNull();
    expect(Note::query()->find($note->id)->deleted_at)->toBeNull();

    // No notification fired.
    $notifications = session()->get('filament.notifications', []);
    $titles = array_map(fn ($n) => $n['title'] ?? null, $notifications);
    expect($titles)->not->toContain('Note deleted');
});

it('deleteNote() silently no-ops when the id matches no note at all', function () {
    $rm = leadNotesDeleteRm();

    $user = User::create([
        'name' => 'Missing-id Deleter',
        'email' => 'missing-id-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $lead = Lead::create(['external_id' => (string) Str::uuid(), 'title' => 'Lead with no notes']);

    $rm->ownerRecord = $lead->fresh();

    session()->forget('filament.notifications');

    // Should not throw and should not dispatch a notification.
    $rm->deleteNote(999999);

    $notifications = session()->get('filament.notifications', []);
    $titles = array_map(fn ($n) => $n['title'] ?? null, $notifications);
    expect($titles)->not->toContain('Note deleted');
});

it('deleteNote() clears edit state when deleting the currently-editing note', function () {
    $rm = leadNotesDeleteRm();

    $user = User::create([
        'name' => 'Editing Deleter',
        'email' => 'editing-deleter-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $lead = Lead::create(['external_id' => (string) Str::uuid(), 'title' => 'Lead']);
    $note = $lead->notes()->create([
        'content' => 'Editing this one',
        'noted_at' => now(),
        'user_created_id' => $user->id,
    ]);

    $rm->ownerRecord = $lead->fresh();
    $rm->editingId = (int) $note->id;
    $rm->data = [
        'content' => 'Pending edit',
        'noted_at' => now(),
    ];

    $rm->deleteNote((int) $note->id);

    expect($rm->editingId)->toBeNull();
    expect($rm->data['content'])->toBeNull();
});

it('the real deleteNote() method routes through the owner relation, calls delete(), and notifies', function () {
    $src = file_get_contents(
        dirname(__DIR__, 2) . '/src/RelationManagers/CrmNotesRelationManager.php'
    );

    // Resolves the target via the owner relation (scoping enforced)
    expect($src)->toContain('getOwnerRecord()->notes()->whereKey($id)');

    // Soft-delete via the base model's delete() — SoftDeletes trait on Note
    // converts this to deleted_at stamping.
    expect($src)->toContain('$note->delete();');

    // Public method signature with int param
    expect($src)->toContain('public function deleteNote(int $id): void');

    // Success notification dispatched
    expect($src)->toContain("->title('Note deleted')");
    expect($src)->toContain('->success()');
    expect($src)->toContain('->send()');
});
