{{-- Sparkline rendering of Monitor's last-7-days uptime response-time. Mirrors core CRM's monitor-index.blade.php inline @scope('cell_performance'). --}}
@php
    $record = $getRecord();
    $bars = \VentureDrake\LaravelCrmFilament\Resources\Monitors\MonitorResource::performanceBars($record);
    $max = max($bars) ?: 1;
    $width = 100;
    $height = 28;
    $gap = 2;
    $barWidth = ($width - ($gap * 6)) / 7;
@endphp
<svg viewBox="0 0 {{ $width }} {{ $height }}" width="{{ $width }}" height="{{ $height }}" aria-hidden="true" style="display:inline-block;vertical-align:middle">
    @foreach ($bars as $i => $value)
        @php
            $h = $value > 0 ? max(2, ($value / $max) * $height) : 1;
            $x = $i * ($barWidth + $gap);
            $y = $height - $h;
        @endphp
        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $h }}" rx="1" fill="#05b3a9" />
    @endforeach
</svg>