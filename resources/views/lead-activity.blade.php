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
                                @if (! empty($recordable->content))
                                    <div class="crm-timeline-recordable-body">{{ $recordable->content }}</div>
                                @endif
                                @break
                            @case('task')
                            @case('call')
                            @case('meeting')
                            @case('lunch')
                                @if (! empty($recordable->name))
                                    <div class="crm-timeline-recordable-title">{{ $recordable->name }}</div>
                                @endif
                                @if (! empty($recordable->description))
                                    <div class="crm-timeline-recordable-body">{{ $recordable->description }}</div>
                                @endif
                                @break
                            @case('file')
                                <div class="crm-timeline-recordable-title">{{ $recordable->name ?? $recordable->file }}</div>
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
