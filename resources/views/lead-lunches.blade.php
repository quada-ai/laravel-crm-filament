<div class="crm-lead-lunches" data-testid="crm-lead-lunches">
    @include('laravel-crm-filament::partials.lead-card-styles')

    @php
        $lunchRows = $this->getOwnerRecord()->lunches()->orderBy('created_at', 'desc')->get();
    @endphp

    @if ($editingId === null)
        <div class="crm-card-card crm-card-card--add" data-testid="crm-lead-lunch-add-card">
            <h3 class="crm-card-section-heading">{{ __('laravel-crm-filament::labels.sections.add_lunch') }}</h3>
            <hr class="crm-card-section-divider" />
            <form wire:submit="createLunch" class="crm-card-form">
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-lunch-add-name">{{ __('laravel-crm-filament::labels.fields.name') }}</label>
                    <input
                        id="crm-lead-lunch-add-name"
                        class="crm-card-noted-at"
                        type="text"
                        wire:model="data.name"
                    />
                </div>
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-lunch-add-description">{{ __('laravel-crm-filament::labels.fields.description') }}</label>
                    <textarea
                        id="crm-lead-lunch-add-description"
                        class="crm-card-textarea"
                        wire:model="data.description"
                        rows="3"
                    ></textarea>
                </div>
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-lunch-add-start-at">{{ __('laravel-crm-filament::labels.money.start') }}</label>
                    <input
                        id="crm-lead-lunch-add-start-at"
                        class="crm-card-noted-at"
                        type="datetime-local"
                        wire:model="data.start_at"
                    />
                </div>
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-lunch-add-finish-at">{{ __('laravel-crm-filament::labels.money.finish') }}</label>
                    <input
                        id="crm-lead-lunch-add-finish-at"
                        class="crm-card-noted-at"
                        type="datetime-local"
                        wire:model="data.finish_at"
                    />
                </div>
                <div class="crm-card-field">
                    <label class="crm-card-field-label" for="crm-lead-lunch-add-location">{{ __('laravel-crm-filament::labels.fields.location') }}</label>
                    <input
                        id="crm-lead-lunch-add-location"
                        class="crm-card-noted-at"
                        type="text"
                        wire:model="data.location"
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

    @forelse ($lunchRows as $lunch)
        <div class="crm-card-card" data-lunch-id="{{ $lunch->id }}" data-testid="crm-lead-lunch-card">
            @if ($editingId === $lunch->id)
                <form wire:submit="updateLunch" class="crm-card-form" data-testid="crm-lead-lunch-edit-form">
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
                    <input
                        class="crm-card-noted-at"
                        type="text"
                        wire:model="data.location"
                        placeholder="{{ __('laravel-crm-filament::labels.fields.location') }}"
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
                        {{ $lunch->created_at?->diffForHumans() }} - {{ $lunch->createdByUser?->name }}
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
                                wire:click="editLunch({{ $lunch->id }})"
                                @click="open = false"
                                class="crm-card-dropdown-item"
                                role="menuitem"
                            >Edit</button>
                            <button
                                type="button"
                                wire:click="deleteLunch({{ $lunch->id }})"
                                wire:confirm="Delete this lunch?"
                                @click="open = false"
                                class="crm-card-dropdown-item crm-card-dropdown-item--danger"
                                role="menuitem"
                            >Delete</button>
                        </div>
                    </div>
                </div>
                <div class="crm-card-card-body">
                    <strong>{{ $lunch->name }}</strong>
                    @if ($lunch->description)
                        <div>{{ $lunch->description }}</div>
                    @endif
                    @if ($lunch->location)
                        <div class="crm-card-card-location" data-testid="crm-lead-lunch-location">
                            <em>{{ __('laravel-crm-filament::labels.fields.location') }}:</em> {{ $lunch->location }}
                        </div>
                    @endif
                </div>
                @if ($lunch->start_at || $lunch->finish_at)
                    <div class="crm-card-card-footer">
                        <span class="crm-card-pill">
                            @if ($lunch->start_at && $lunch->finish_at)
                                {{ $lunch->start_at->format('h:i A, M d, Y') }} .. {{ $lunch->finish_at->format('h:i A, M d, Y') }}
                            @elseif ($lunch->start_at)
                                {{ $lunch->start_at->format('h:i A, M d, Y') }}
                            @else
                                {{ $lunch->finish_at->format('h:i A, M d, Y') }}
                            @endif
                        </span>
                    </div>
                @endif
            @endif
        </div>
    @empty
        <div class="crm-card-empty">No lunches yet.</div>
    @endforelse
</div>
