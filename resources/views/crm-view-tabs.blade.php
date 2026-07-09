@php
    $resource = static::getResource();
    $relations = array_values($resource::getRelations());
    $ownerRecord = $this->getRecord();
    $activeTab = (int) ($this->activeTab ?? 0);
@endphp

<div class="crm-view-tabs-wrapper" data-testid="crm-view-tabs">
    <div class="crm-view-tabs-strip" role="tablist">
        @foreach ($relations as $index => $rmClass)
            <button
                type="button"
                role="tab"
                wire:click="setActiveTab({{ $index }})"
                class="crm-view-tab @if ($activeTab === $index) crm-view-tab--active @endif"
                aria-selected="{{ $activeTab === $index ? 'true' : 'false' }}"
                data-tab-index="{{ $index }}"
            >
                {{ $rmClass::getTitle($ownerRecord, static::class) }}
            </button>
        @endforeach
    </div>

    <div class="crm-view-tab-content" role="tabpanel">
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

@once
    <style>
        .crm-view-tabs-strip {
            display: flex;
            gap: 0.25rem;
            border-bottom: 1px solid rgb(229 231 235);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            overflow-x: auto;
        }
        .crm-view-tab {
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            cursor: pointer;
            background: transparent;
            border: none;
            color: rgb(75 85 99);
            font-weight: 500;
            font-size: 0.875rem;
            white-space: nowrap;
        }
        .crm-view-tab:hover {
            background: rgb(243 244 246);
            color: rgb(17 24 39);
        }
        .crm-view-tab--active {
            background: rgb(220 252 231);
            color: rgb(5 179 169);
        }
        html.dark .crm-view-tabs-strip { border-bottom-color: rgb(55 65 81); }
        html.dark .crm-view-tab { color: rgb(156 163 175); }
        html.dark .crm-view-tab:hover { background: rgb(31 41 55); color: rgb(229 231 235); }
        html.dark .crm-view-tab--active { background: rgba(5, 179, 169, 0.2); color: rgb(94 234 212); }
    </style>
@endonce