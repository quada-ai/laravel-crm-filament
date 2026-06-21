{{-- Read-only card for a Call. Accepts: $record (Call) --}}
<div class="crm-card-card">
    <div class="crm-card-card-head">
        <div class="crm-card-card-title">{{ $record->name }}</div>
    </div>
    <div class="crm-card-badges">
        @if ($record->start_at)
            <span class="crm-card-pill">{{ __('laravel-crm-filament::labels.money.start_at') }} {{ $record->start_at->format('h:i A') }} on {{ $record->start_at->format('M d, Y') }}</span>
        @endif
        @if ($record->finish_at)
            <span class="crm-card-pill">{{ __('laravel-crm-filament::labels.money.finish_at') }} {{ $record->finish_at->format('h:i A') }} on {{ $record->finish_at->format('M d, Y') }}</span>
        @endif
    </div>

    <hr class="crm-card-section-divider crm-card-section-divider--inset" />
    <h4 class="crm-card-section-title">{{ __('laravel-crm-filament::labels.fields.guests') }}</h4>
    @php
        $guestContacts = $record->relationLoaded('contacts')
            ? $record->contacts->filter(fn ($c) => $c->entityable !== null)
            : $record->contacts()->get()->filter(fn ($c) => $c->entityable !== null);
    @endphp
    @if ($guestContacts->count() > 0)
        <div class="crm-card-guests">
            @foreach ($guestContacts as $guest)
                <span class="crm-card-guest-item">
                    <span class="crm-card-guest-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/></svg>
                    </span>
                    <span class="crm-card-guest-link">{{ $guest->entityable->name }}</span>
                </span>
            @endforeach
        </div>
    @endif

    <hr class="crm-card-section-divider crm-card-section-divider--inset" />
    <h4 class="crm-card-section-title">{{ __('laravel-crm-filament::labels.fields.location') }}</h4>
    @if ($record->location)
        <div class="crm-card-section-content">{{ $record->location }}</div>
    @endif

    <hr class="crm-card-section-divider crm-card-section-divider--inset" />
    <h4 class="crm-card-section-title">{{ __('laravel-crm-filament::labels.fields.description') }}</h4>
    @if ($record->description)
        <div class="crm-card-section-content">{{ $record->description }}</div>
    @endif
</div>
