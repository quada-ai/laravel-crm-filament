{{-- Read-only card for a Task. Accepts: $record (Task) --}}
<div class="crm-card-card">
    <div class="crm-card-card-head">
        <div class="crm-card-card-title">{{ $record->name }}</div>
    </div>
    <div class="crm-card-badges">
        @if ($record->completed_at)
            <span class="crm-card-badge crm-card-badge--success">{{ __('laravel-crm-filament::labels.status.complete') }}</span>
        @else
            <span class="crm-card-badge crm-card-badge--primary">{{ __('laravel-crm-filament::labels.status.pending') }}</span>
        @endif
        @if ($record->due_at)
            <span class="crm-card-pill">{{ __('laravel-crm-filament::labels.money.due') }} {{ $record->due_at->format('h:i A') }} on {{ $record->due_at->format('M d, Y') }}</span>
        @endif
    </div>
    @if ($record->description)
        <div class="crm-card-card-body">{{ $record->description }}</div>
    @endif
    @if ($record->ownerUser || $record->assignedToUser)
        <div class="crm-card-card-footer crm-card-card-attribution">
            @if ($record->ownerUser)
                <small>{{ __('laravel-crm-filament::labels.fields.requested_by') }} <span class="crm-card-attribution-name">{{ $record->ownerUser->name }}</span></small>
            @endif
            @if ($record->ownerUser && $record->assignedToUser)
                <small class="crm-card-attribution-sep">|</small>
            @endif
            @if ($record->assignedToUser)
                <small>{{ __('laravel-crm-filament::labels.fields.assigned_to') }} <span class="crm-card-attribution-name">{{ $record->assignedToUser->name }}</span></small>
            @endif
        </div>
    @endif
</div>
