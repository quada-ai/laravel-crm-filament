<div class="crm-lead-tasks" data-testid="crm-lead-tasks">
    @include('laravel-crm-filament::partials.lead-card-styles')

    @php
        $taskRows = $this->getOwnerRecord()->tasks()->orderBy('created_at', 'desc')->get();
    @endphp

    @if ($editingId === null)
        <div class="crm-card-card crm-card-card--add" data-testid="crm-lead-task-add-card">
            <h3 class="crm-card-section-heading">{{ __('laravel-crm-filament::labels.sections.add_task') }}</h3>
            <hr class="crm-card-section-divider" />
            <form wire:submit="createTask" class="crm-card-form">
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-task-add-name">{{ __('laravel-crm-filament::labels.fields.name') }}</label>
                    <input
                        id="crm-lead-task-add-name"
                        class="crm-card-noted-at"
                        type="text"
                        wire:model="data.name"
                    />
                </div>
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-task-add-description">{{ __('laravel-crm-filament::labels.fields.description') }}</label>
                    <textarea
                        id="crm-lead-task-add-description"
                        class="crm-card-textarea"
                        wire:model="data.description"
                        rows="3"
                    ></textarea>
                </div>
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-task-add-due-at">{{ __('laravel-crm-filament::labels.money.due') }}</label>
                    <input
                        id="crm-lead-task-add-due-at"
                        class="crm-card-noted-at"
                        type="datetime-local"
                        wire:model="data.due_at"
                    />
                </div>
                @error('data.name')
                    <div class="crm-card-empty" style="text-align:left;color:var(--crm-card-danger);">{{ $message }}</div>
                @enderror
                <hr class="crm-card-section-divider crm-card-section-divider--footer" />
                <div class="crm-card-form-actions">
                    <button
                        type="submit"
                        class="crm-card-btn crm-card-btn--primary"
                        wire:loading.attr="disabled"
                    >{{ __('laravel-crm-filament::labels.actions.save') }}</button>
                </div>
            </form>
        </div>
    @endif

    @forelse ($taskRows as $task)
        <div class="crm-card-card" data-task-id="{{ $task->id }}" data-testid="crm-lead-task-card">
            @if ($editingId === $task->id)
                <form wire:submit="updateTask" class="crm-card-form" data-testid="crm-lead-task-edit-form">
                    <input
                        class="crm-card-noted-at"
                        type="text"
                        wire:model="data.name"
                    />
                    <textarea
                        class="crm-card-textarea"
                        wire:model="data.description"
                        rows="3"
                    ></textarea>
                    <input
                        class="crm-card-noted-at"
                        type="datetime-local"
                        wire:model="data.due_at"
                    />
                    <div class="crm-card-form-actions">
                        <button
                            type="submit"
                            class="crm-card-btn crm-card-btn--primary"
                            wire:loading.attr="disabled"
                        >{{ __('laravel-crm-filament::labels.actions.save') }}</button>
                        <button
                            type="button"
                            wire:click="cancelEdit"
                            class="crm-card-btn"
                        >{{ __('laravel-crm-filament::labels.actions.cancel') }}</button>
                    </div>
                </form>
            @else
                <div class="crm-card-card-head">
                    <div class="crm-card-card-meta">
                        {{ $task->created_at?->diffForHumans() }} - {{ $task->createdByUser?->name }}
                    </div>
                    <div
                        x-data="{ open: false }"
                        @click.outside="open = false"
                        class="crm-card-dropdown"
                    >
                        <button
                            type="button"
                            @click="open = !open"
                            class="crm-card-dropdown-btn"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            x-bind:aria-expanded="open ? 'true' : 'false'"
                        >&hellip;</button>
                        <div
                            x-show="open"
                            x-cloak
                            class="crm-card-dropdown-menu"
                            role="menu"
                        >
                            <button
                                type="button"
                                wire:click="editTask({{ $task->id }})"
                                @click="open = false"
                                class="crm-card-dropdown-item"
                                role="menuitem"
                            >Edit</button>
                            <button
                                type="button"
                                wire:click="deleteTask({{ $task->id }})"
                                wire:confirm="Delete this task?"
                                @click="open = false"
                                class="crm-card-dropdown-item crm-card-dropdown-item--danger"
                                role="menuitem"
                            >Delete</button>
                        </div>
                    </div>
                </div>
                <div class="crm-card-card-body">
                    <strong>{{ $task->name }}</strong>
                    @if ($task->description)
                        <div>{{ $task->description }}</div>
                    @endif
                </div>
                @if ($task->due_at)
                    <div class="crm-card-card-footer">
                        <span class="crm-card-pill">Due at {{ $task->due_at->format('h:i A') }} on {{ $task->due_at->format('M d, Y') }}</span>
                    </div>
                @endif
            @endif
        </div>
    @empty
        <div class="crm-card-empty">No tasks yet.</div>
    @endforelse
</div>
