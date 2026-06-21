<div class="crm-lead-calls" data-testid="crm-lead-calls">
    @include('laravel-crm-filament::partials.lead-card-styles')

    @php
        $callRows = $this->getOwnerRecord()->calls()->orderBy('created_at', 'desc')->get();
    @endphp

    @if ($editingId === null)
        <div class="crm-card-card crm-card-card--add" data-testid="crm-lead-call-add-card">
            <h3 class="crm-card-section-heading">{{ __('laravel-crm-filament::labels.sections.add_call') }}</h3>
            <hr class="crm-card-section-divider" />
            <form wire:submit="createCall" class="crm-card-form">
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-call-add-name">{{ __('laravel-crm-filament::labels.fields.name') }}</label>
                    <input
                        id="crm-lead-call-add-name"
                        class="crm-card-noted-at"
                        type="text"
                        wire:model="data.name"
                    />
                </div>
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-call-add-description">{{ __('laravel-crm-filament::labels.fields.description') }}</label>
                    <textarea
                        id="crm-lead-call-add-description"
                        class="crm-card-textarea"
                        wire:model="data.description"
                        rows="3"
                    ></textarea>
                </div>
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-call-add-start-at">{{ __('laravel-crm-filament::labels.money.start') }}</label>
                    <input
                        id="crm-lead-call-add-start-at"
                        class="crm-card-noted-at"
                        type="datetime-local"
                        wire:model="data.start_at"
                    />
                </div>
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-call-add-finish-at">{{ __('laravel-crm-filament::labels.money.finish') }}</label>
                    <input
                        id="crm-lead-call-add-finish-at"
                        class="crm-card-noted-at"
                        type="datetime-local"
                        wire:model="data.finish_at"
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

    @forelse ($callRows as $call)
        <div class="crm-card-card" data-call-id="{{ $call->id }}" data-testid="crm-lead-call-card">
            @if ($editingId === $call->id)
                <form wire:submit="updateCall" class="crm-card-form" data-testid="crm-lead-call-edit-form">
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
                        wire:model="data.start_at"
                    />
                    <input
                        class="crm-card-noted-at"
                        type="datetime-local"
                        wire:model="data.finish_at"
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
                        {{ $call->created_at?->diffForHumans() }} - {{ $call->createdByUser?->name }}
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
                                wire:click="editCall({{ $call->id }})"
                                @click="open = false"
                                class="crm-card-dropdown-item"
                                role="menuitem"
                            >Edit</button>
                            <button
                                type="button"
                                wire:click="deleteCall({{ $call->id }})"
                                wire:confirm="Delete this call?"
                                @click="open = false"
                                class="crm-card-dropdown-item crm-card-dropdown-item--danger"
                                role="menuitem"
                            >Delete</button>
                        </div>
                    </div>
                </div>
                <div class="crm-card-card-body">
                    <strong>{{ $call->name }}</strong>
                    @if ($call->description)
                        <div>{{ $call->description }}</div>
                    @endif
                </div>
                @if ($call->start_at || $call->finish_at)
                    <div class="crm-card-card-footer">
                        <span class="crm-card-pill">
                            @if ($call->start_at && $call->finish_at)
                                {{ $call->start_at->format('h:i A, M d, Y') }} .. {{ $call->finish_at->format('h:i A, M d, Y') }}
                            @elseif ($call->start_at)
                                {{ $call->start_at->format('h:i A, M d, Y') }}
                            @else
                                {{ $call->finish_at->format('h:i A, M d, Y') }}
                            @endif
                        </span>
                    </div>
                @endif
            @endif
        </div>
    @empty
        <div class="crm-card-empty">No calls yet.</div>
    @endforelse
</div>
