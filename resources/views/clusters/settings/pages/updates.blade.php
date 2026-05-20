<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Current version">
            <div class="text-lg font-semibold">{{ $this->currentVersion }}</div>
            @if ($this->installId)
                <div class="text-sm text-gray-500 mt-1">Install ID: {{ $this->installId }}</div>
            @endif
        </x-filament::section>

        <x-filament::section heading="Latest available">
            @if ($this->latestVersion)
                <div class="text-lg font-semibold">{{ $this->latestVersion }}</div>
                @if ($this->isUpToDate)
                    <div class="text-sm text-success-600 mt-1">You are up to date.</div>
                @else
                    <div class="text-sm text-warning-600 mt-1">A newer version is available.</div>
                @endif
            @else
                <div class="text-sm text-gray-500">No version information available yet. Run a check to fetch the latest release.</div>
            @endif

            @if ($this->releaseNotes)
                <div class="prose dark:prose-invert mt-4">
                    {!! $this->releaseNotes !!}
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
