<?php

declare(strict_types=1);

beforeEach(function () {
    $this->bladePath = __DIR__ . '/../../resources/views/lead-notes.blade.php';
});

it('renders the lead-notes blade file at the expected path', function () {
    expect(file_exists($this->bladePath))->toBeTrue();
});

it('declares the @once <style> block with CSS custom properties for dark mode', function () {
    $blade = file_get_contents($this->bladePath);

    expect($blade)->toContain('@once');
    expect($blade)->toContain('<style>');
    expect($blade)->toContain('@endonce');

    expect($blade)->toContain('--crm-note-bg:');
    expect($blade)->toContain('--crm-note-text:');
    expect($blade)->toContain('--crm-note-pill-bg:');
    expect($blade)->toContain('html.dark .crm-lead-notes');
});

it('exposes the add-note form wired to createNote with content + noted_at bindings', function () {
    $blade = file_get_contents($this->bladePath);

    expect($blade)->toContain('@if ($editingId === null)');
    expect($blade)->toContain('wire:submit="createNote"');
    expect($blade)->toContain('wire:model="data.content"');
    expect($blade)->toContain('wire:model="data.noted_at"');
    expect($blade)->toContain("__('laravel-crm-filament::labels.actions.save')");
});

it('loops getOwnerRecord()->notes() sorted by created_at desc', function () {
    $blade = file_get_contents($this->bladePath);

    expect($blade)->toContain('$this->getOwnerRecord()->notes()');
    expect($blade)->toContain("->orderBy('created_at', 'desc')");
    expect($blade)->toContain('@forelse');
});

it('renders the relative-time + creator-name header', function () {
    $blade = file_get_contents($this->bladePath);

    expect($blade)->toContain('$note->created_at?->diffForHumans()');
    expect($blade)->toContain('$note->createdByUser?->name');
});

it('renders the note body with escaped output, never {!! !!}', function () {
    $blade = file_get_contents($this->bladePath);

    expect($blade)->toContain('{{ $note->content }}');
    expect($blade)->not->toContain('{!! $note->content !!}');
    expect($blade)->not->toContain('{!!');
});

it('renders the Noted-at footer pill in the prescribed format', function () {
    $blade = file_get_contents($this->bladePath);

    expect($blade)->toContain('@if ($note->noted_at)');
    expect($blade)->toContain('Noted at');
    expect($blade)->toContain("\$note->noted_at->format('h:i A')");
    expect($blade)->toContain("\$note->noted_at->format('M d, Y')");
    expect($blade)->toContain('crm-note-pill');
});

it('exposes a three-dot dropdown with Edit and Delete wired to editNote / deleteNote', function () {
    $blade = file_get_contents($this->bladePath);

    expect($blade)->toContain('crm-note-dropdown');
    expect($blade)->toContain('x-data="{ open: false }"');
    expect($blade)->toContain('wire:click="editNote({{ $note->id }})"');
    expect($blade)->toContain('wire:click="deleteNote({{ $note->id }})"');
    expect($blade)->toContain('>Edit<');
    expect($blade)->toContain('>Delete<');
});

it('swaps the card body for the inline edit form when $editingId === $note->id', function () {
    $blade = file_get_contents($this->bladePath);

    expect($blade)->toContain('@if ($editingId === $note->id)');
    expect($blade)->toContain('wire:submit="updateNote"');
    expect($blade)->toContain('wire:click="cancelEdit"');
});

it('binds the edit form inputs to the same data.content / data.noted_at state', function () {
    $blade = file_get_contents($this->bladePath);

    // Two occurrences of each binding (one for create, one for edit).
    expect(substr_count($blade, 'wire:model="data.content"'))->toBeGreaterThanOrEqual(2);
    expect(substr_count($blade, 'wire:model="data.noted_at"'))->toBeGreaterThanOrEqual(2);
});

it('renders an empty-state message when the lead has no notes', function () {
    $blade = file_get_contents($this->bladePath);

    expect($blade)->toContain('@empty');
    expect($blade)->toContain('No notes yet');
});
