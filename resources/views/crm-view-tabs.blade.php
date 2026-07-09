@php
    $resource = static::getResource();
    $relations = array_values($resource::getRelations());
    $ownerRecord = $this->getRecord();
    $activeTab = (int) ($this->activeTab ?? 0);
@endphp

<div class="crm-view-tabs-wrapper" data-testid="crm-view-tabs">
    <x-filament::tabs>
        @foreach ($relations as $index => $rmClass)
            <x-filament::tabs.item
                :active="$activeTab === $index"
                wire:click="setActiveTab({{ $index }})"
                data-tab-index="{{ $index }}"
            >
                {{ $rmClass::getTitle($ownerRecord, static::class) }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    <div class="crm-view-tab-content" style="margin-top: 1rem;" role="tabpanel">
        @foreach ($relations as $index => $rmClass)
            @if ($activeTab === $index)
                @livewire(
                    $rmClass,
                    ['ownerRecord' => $ownerRecord, 'pageClass' => static::class],
                    key('crm-view-rm-' . $index . '-' . $ownerRecord->getKey())
                )
            @endif
        @endforeach
    </div>
</div>
