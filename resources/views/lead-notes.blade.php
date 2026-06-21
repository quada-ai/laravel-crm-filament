<div class="crm-lead-notes" data-testid="crm-lead-notes">
    @once
        <style>
            .crm-lead-notes {
                --crm-note-bg: #ffffff;
                --crm-note-border: rgba(0, 0, 0, 0.06);
                --crm-note-text: #111827;
                --crm-note-muted: #6b7280;
                --crm-note-subtle: #9ca3af;
                --crm-note-pill-bg: rgba(0, 0, 0, 0.05);
                --crm-note-pill-color: #374151;
                --crm-note-input-bg: #ffffff;
                --crm-note-input-border: rgba(0, 0, 0, 0.12);
                --crm-note-input-color: #111827;
                --crm-note-primary: #05b3a9;
                --crm-note-primary-hover: #047d75;
                --crm-note-danger: #dc2626;

                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }
            html.dark .crm-lead-notes {
                --crm-note-bg: transparent;
                --crm-note-border: rgba(255, 255, 255, 0.1);
                --crm-note-text: #ffffff;
                --crm-note-muted: #9ca3af;
                --crm-note-subtle: #6b7280;
                --crm-note-pill-bg: rgba(255, 255, 255, 0.05);
                --crm-note-pill-color: #d1d5db;
                --crm-note-input-bg: rgba(255, 255, 255, 0.05);
                --crm-note-input-border: rgba(255, 255, 255, 0.1);
                --crm-note-input-color: #ffffff;
                --crm-note-primary: #2dd4bf;
                --crm-note-primary-hover: #0d9488;
                --crm-note-danger: #f87171;
            }
            .crm-note-card {
                position: relative;
                background: var(--crm-note-bg);
                border: 1px solid var(--crm-note-border);
                border-radius: 0.5rem;
                padding: 0.875rem 1rem;
                color: var(--crm-note-text);
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            }
            .crm-note-card--add { border-style: dashed; }
            .crm-note-card-head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 0.5rem;
                margin-bottom: 0.5rem;
            }
            .crm-note-card-meta { font-size: 0.8125rem; font-weight: 600; color: var(--crm-note-muted); }
            .crm-note-card-body {
                white-space: pre-wrap;
                word-break: break-word;
                font-size: 0.875rem;
                line-height: 1.45;
                margin-bottom: 0.5rem;
            }
            .crm-note-card-footer { margin-top: 0.5rem; }
            .crm-note-pill {
                display: inline-block;
                padding: 0.125rem 0.5rem;
                border-radius: 9999px;
                background: var(--crm-note-pill-bg);
                color: var(--crm-note-pill-color);
                font-size: 0.6875rem;
                font-weight: 600;
            }
            .crm-note-empty {
                color: var(--crm-note-muted);
                font-size: 0.875rem;
                text-align: center;
                padding: 1.5rem 1rem;
            }
            .crm-note-form { display: flex; flex-direction: column; gap: 0.5rem; }
            .crm-note-textarea {
                width: 100%;
                min-height: 90px;
                padding: 0.5rem 0.75rem;
                border-radius: 0.5rem;
                border: 1px solid var(--crm-note-input-border);
                background: var(--crm-note-input-bg);
                color: var(--crm-note-input-color);
                font-size: 0.875rem;
                line-height: 1.4;
                resize: vertical;
            }
            .crm-note-noted-at {
                padding: 0.5rem 0.75rem;
                border-radius: 0.5rem;
                border: 1px solid var(--crm-note-input-border);
                background: var(--crm-note-input-bg);
                color: var(--crm-note-input-color);
                font-size: 0.875rem;
            }
            .crm-note-form-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
            .crm-note-btn {
                padding: 0.4375rem 0.875rem;
                border-radius: 0.5rem;
                font-size: 0.8125rem;
                font-weight: 600;
                border: 1px solid var(--crm-note-input-border);
                background: transparent;
                color: var(--crm-note-text);
                cursor: pointer;
            }
            .crm-note-btn:disabled { opacity: 0.5; cursor: not-allowed; }
            .crm-note-btn--primary {
                background: var(--crm-note-primary);
                border-color: var(--crm-note-primary);
                color: #ffffff;
            }
            .crm-note-btn--primary:hover { background: var(--crm-note-primary-hover); }
            .crm-note-dropdown { position: relative; }
            .crm-note-dropdown-btn {
                background: transparent;
                border: 0;
                color: var(--crm-note-muted);
                cursor: pointer;
                font-size: 1.125rem;
                line-height: 1;
                padding: 0.125rem 0.375rem;
                border-radius: 0.25rem;
            }
            .crm-note-dropdown-btn:hover { color: var(--crm-note-text); background: var(--crm-note-pill-bg); }
            .crm-note-dropdown-menu {
                position: absolute;
                right: 0;
                top: 100%;
                margin-top: 0.25rem;
                min-width: 140px;
                background: var(--crm-note-bg);
                border: 1px solid var(--crm-note-border);
                border-radius: 0.5rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
                z-index: 10;
                overflow: hidden;
            }
            .crm-note-dropdown-item {
                display: block;
                width: 100%;
                text-align: left;
                padding: 0.5rem 0.75rem;
                background: transparent;
                border: 0;
                color: var(--crm-note-text);
                font-size: 0.8125rem;
                cursor: pointer;
            }
            .crm-note-dropdown-item:hover { background: var(--crm-note-pill-bg); }
            .crm-note-dropdown-item--danger { color: var(--crm-note-danger); }
        </style>
    @endonce

    @php
        $noteRows = $this->getOwnerRecord()->notes()->orderBy('created_at', 'desc')->get();
    @endphp

    @if ($editingId === null)
        <div class="crm-note-card crm-note-card--add" data-testid="crm-lead-note-add-card">
            <form wire:submit="createNote" class="crm-note-form">
                <textarea
                    class="crm-note-textarea"
                    wire:model="data.content"
                    rows="4"
                ></textarea>
                <input
                    class="crm-note-noted-at"
                    type="datetime-local"
                    wire:model="data.noted_at"
                />
                <div class="crm-note-form-actions">
                    <button
                        type="submit"
                        class="crm-note-btn crm-note-btn--primary"
                        wire:loading.attr="disabled"
                    >{{ __('laravel-crm-filament::labels.actions.save') }}</button>
                </div>
                @error('data.content')
                    <div class="crm-note-empty" style="text-align:left;color:var(--crm-note-danger);">{{ $message }}</div>
                @enderror
            </form>
        </div>
    @endif

    @forelse ($noteRows as $note)
        <div class="crm-note-card" data-note-id="{{ $note->id }}" data-testid="crm-lead-note-card">
            @if ($editingId === $note->id)
                <form wire:submit="updateNote" class="crm-note-form" data-testid="crm-lead-note-edit-form">
                    <textarea
                        class="crm-note-textarea"
                        wire:model="data.content"
                        rows="4"
                    ></textarea>
                    <input
                        class="crm-note-noted-at"
                        type="datetime-local"
                        wire:model="data.noted_at"
                    />
                    <div class="crm-note-form-actions">
                        <button
                            type="submit"
                            class="crm-note-btn crm-note-btn--primary"
                            wire:loading.attr="disabled"
                        >{{ __('laravel-crm-filament::labels.actions.save') }}</button>
                        <button
                            type="button"
                            wire:click="cancelEdit"
                            class="crm-note-btn"
                        >{{ __('laravel-crm-filament::labels.actions.cancel') }}</button>
                    </div>
                </form>
            @else
                <div class="crm-note-card-head">
                    <div class="crm-note-card-meta">
                        {{ $note->created_at?->diffForHumans() }} - {{ $note->createdByUser?->name }}
                    </div>
                    <div
                        x-data="{ open: false }"
                        @click.outside="open = false"
                        class="crm-note-dropdown"
                    >
                        <button
                            type="button"
                            @click="open = !open"
                            class="crm-note-dropdown-btn"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            x-bind:aria-expanded="open ? 'true' : 'false'"
                        >&hellip;</button>
                        <div
                            x-show="open"
                            x-cloak
                            class="crm-note-dropdown-menu"
                            role="menu"
                        >
                            <button
                                type="button"
                                wire:click="editNote({{ $note->id }})"
                                @click="open = false"
                                class="crm-note-dropdown-item"
                                role="menuitem"
                            >Edit</button>
                            <button
                                type="button"
                                wire:click="deleteNote({{ $note->id }})"
                                wire:confirm="Delete this note?"
                                @click="open = false"
                                class="crm-note-dropdown-item crm-note-dropdown-item--danger"
                                role="menuitem"
                            >Delete</button>
                        </div>
                    </div>
                </div>
                <div class="crm-note-card-body">{{ $note->content }}</div>
                @if ($note->noted_at)
                    <div class="crm-note-card-footer">
                        <span class="crm-note-pill">Noted at {{ $note->noted_at->format('h:i A') }} on {{ $note->noted_at->format('M d, Y') }}</span>
                    </div>
                @endif
            @endif
        </div>
    @empty
        <div class="crm-note-empty">No notes yet.</div>
    @endforelse
</div>
