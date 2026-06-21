<div class="crm-lead-files" data-testid="crm-lead-files">
    @include('laravel-crm-filament::partials.lead-card-styles')

    @php
        $fileRows = $this->getOwnerRecord()->files()->orderBy('created_at', 'desc')->get();
    @endphp

    <div class="crm-card-card crm-card-card--add" data-testid="crm-lead-file-add-card">
        <h3 class="crm-card-section-heading">{{ __('laravel-crm-filament::labels.sections.add_file') }}</h3>
        <hr class="crm-card-section-divider" />
        <form wire:submit="createFile" class="crm-card-form">
            {{ $this->form }}
            <hr class="crm-card-section-divider crm-card-section-divider--footer" />
            <div class="crm-card-form-actions">
                <button
                    type="submit"
                    class="crm-card-btn crm-card-btn--primary"
                    wire:loading.attr="disabled"
                >{{ __('laravel-crm-filament::labels.actions.upload') }}</button>
            </div>
        </form>
    </div>

    @forelse ($fileRows as $file)
        <div class="crm-card-card" data-file-id="{{ $file->id }}" data-testid="crm-lead-file-card">
            <div class="crm-card-card-head">
                <div class="crm-card-card-meta">
                    {{ $file->created_at?->diffForHumans() }} - {{ $file->createdByUser?->name }}
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
                        @php
                            $downloadUrl = $this->downloadFile($file->id);
                        @endphp
                        @if ($downloadUrl)
                            <a
                                href="{{ $downloadUrl }}"
                                target="_blank"
                                rel="noopener"
                                @click="open = false"
                                class="crm-card-dropdown-item"
                                role="menuitem"
                            >{{ __('laravel-crm-filament::labels.actions.download') }}</a>
                        @endif
                        <button
                            type="button"
                            wire:click="deleteFile({{ $file->id }})"
                            wire:confirm="Delete this file?"
                            @click="open = false"
                            class="crm-card-dropdown-item crm-card-dropdown-item--danger"
                            role="menuitem"
                        >Delete</button>
                    </div>
                </div>
            </div>
            <div class="crm-card-card-body">
                <strong>{{ $file->name ?? $file->file }}</strong>
                @if ($file->title)
                    <div>{{ $file->title }}</div>
                @endif
            </div>
            <div class="crm-card-card-footer">
                <span class="crm-card-pill">
                    @if ($file->filesize)
                        {{ $file->filesize }} bytes
                    @endif
                    @if ($file->filesize && $file->mime)
                        ·
                    @endif
                    @if ($file->mime)
                        {{ $file->mime }}
                    @endif
                </span>
            </div>
        </div>
    @empty
        <div class="crm-card-empty">No files yet.</div>
    @endforelse
</div>
