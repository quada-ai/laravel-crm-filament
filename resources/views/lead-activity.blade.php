<div class="crm-lead-activity" data-testid="crm-lead-activity">
    @include('laravel-crm-filament::partials.lead-card-styles')

    @php
        $activityRows = $this->getOwnerRecord()
            ->timelineActivities()
            ->orderBy('created_at', 'desc')
            ->get();
    @endphp

    @forelse ($activityRows as $i => $activity)
        @php
            $userName = $activity->causeable->name ?? null;
            $entityType = $activity->recordable_type
                ? strtolower(class_basename($activity->recordable_type))
                : null;
            $verb = $activity->event ?: 'created';
            $title = ($userName ? $userName . ' ' . $verb . ' a ' : ucfirst($verb) . ' a ')
                . ($entityType ?? 'activity');
            $isLast = $loop->last;
            $recordable = $activity->recordable;
        @endphp

        <div class="crm-timeline-item @if ($isLast) crm-timeline-item--last @endif" data-testid="crm-lead-activity-item">
            <div class="crm-timeline-rail">
                <span class="crm-timeline-bullet" aria-hidden="true"></span>
                @if (! $isLast)
                    <span class="crm-timeline-connector" aria-hidden="true"></span>
                @endif
            </div>
            <div class="crm-timeline-body">
                <div class="crm-timeline-title">{{ $title }}</div>
                <div class="crm-timeline-subtitle">{{ $activity->created_at?->format('m/d/Y h:i A') }}</div>
                @if ($recordable)
                    <div class="crm-timeline-recordable">
                        @switch($entityType)
                            @case('note')
                                @include('laravel-crm-filament::partials.lead-card-note', ['record' => $recordable])
                                @break
                            @case('task')
                                @include('laravel-crm-filament::partials.lead-card-task', ['record' => $recordable])
                                @break
                            @case('call')
                                @include('laravel-crm-filament::partials.lead-card-call', ['record' => $recordable])
                                @break
                            @case('meeting')
                                @include('laravel-crm-filament::partials.lead-card-meeting', ['record' => $recordable])
                                @break
                            @case('lunch')
                                @include('laravel-crm-filament::partials.lead-card-lunch', ['record' => $recordable])
                                @break
                            @case('file')
                                @include('laravel-crm-filament::partials.lead-card-file', ['record' => $recordable])
                                @break
                        @endswitch
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="crm-card-empty">No activity yet.</div>
    @endforelse
</div>
