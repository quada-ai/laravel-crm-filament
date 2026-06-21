{{-- Read-only card for a File. Accepts: $record (File) --}}
@php
    $size = (int) ($record->filesize ?? 0);
    if ($size >= 1024 * 1024) {
        $formattedSize = round($size / (1024 * 1024), 2) . ' MB';
    } elseif ($size >= 1024) {
        $formattedSize = round($size / 1024, 2) . ' KB';
    } else {
        $formattedSize = $size . ' B';
    }
@endphp
<div class="crm-card-card">
    <div class="crm-card-card-head">
        <div class="crm-card-card-title">{{ $record->name ?? $record->file }}</div>
    </div>
    <div class="crm-card-badges">
        @if ($record->mime)
            <span class="crm-card-pill">{{ $record->mime }}</span>
        @endif
        @if ($record->filesize)
            <span class="crm-card-pill">{{ $formattedSize }}</span>
        @endif
    </div>
    <div class="crm-card-card-attribution">
        <small>{{ $record->created_at?->diffForHumans() }}
            @if ($record->createdByUser)
                &mdash; {{ $record->createdByUser->name }}
            @endif
        </small>
    </div>
</div>
