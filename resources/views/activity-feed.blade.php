<x-filament-panels::page>
    @include('laravel-crm-filament::partials.crm-card-styles')

    @once
        <style>
            .crm-activity-feed .crm-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                border-radius: 0.5rem;
                padding: 0.375rem 0.875rem;
                font-size: 0.875rem;
                font-weight: 500;
                line-height: 1.25rem;
                cursor: pointer;
                border: 1px solid rgb(209 213 219);
                background: #fff;
                color: rgb(55 65 81);
                transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
            }
            .crm-activity-feed .crm-btn:hover {
                background: rgb(243 244 246);
            }
            .crm-activity-feed .crm-btn.is-active {
                background: #05b3a9;
                color: #fff;
                border-color: #05b3a9;
            }
            .crm-activity-feed .crm-row {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 1rem;
            }
            .crm-activity-feed .crm-timeline {
                margin-top: 0.5rem;
            }
            html.dark .crm-activity-feed .crm-btn {
                background: rgb(31 41 55);
                color: rgb(229 231 235);
                border-color: rgb(75 85 99);
            }
            html.dark .crm-activity-feed .crm-btn:hover {
                background: rgb(55 65 81);
            }
            html.dark .crm-activity-feed .crm-btn.is-active {
                background: #05b3a9;
                color: #fff;
                border-color: #05b3a9;
            }
        </style>
    @endonce

    @php
        $activities = $this->getActivities();
        $tabs = ['all', 'notes', 'tasks', 'calls', 'meetings', 'lunches', 'files'];
    @endphp

    <div class="crm-activity-feed" data-testid="crm-activity-feed">
        <div class="crm-row crm-activity-scope" data-testid="crm-activity-scope">
            <button
                type="button"
                wire:click="setScope('mine')"
                class="crm-btn @if ($scope === 'mine') is-active @endif"
            >
                {{ __('laravel-crm-filament::labels.sales.my_activity') }}
            </button>
            <button
                type="button"
                wire:click="setScope('all')"
                class="crm-btn @if ($scope === 'all') is-active @endif"
            >
                {{ __('laravel-crm-filament::labels.sales.all_activity') }}
            </button>
        </div>

        <div class="crm-row crm-activity-tabs" data-testid="crm-activity-tabs">
            @foreach ($tabs as $t)
                <button
                    type="button"
                    wire:click="setTab('{{ $t }}')"
                    class="crm-btn @if ($tab === $t) is-active @endif"
                    data-tab="{{ $t }}"
                >
                    {{ ucfirst($t) }}
                </button>
            @endforeach
        </div>

        <div class="crm-card-area-activity">
            <div class="crm-timeline" data-testid="crm-timeline">
                @forelse ($activities as $activity)
                    @include('laravel-crm-filament::crm-activity', ['activity' => $activity, 'last' => $loop->last])
                @empty
                    <div class="crm-card-empty">No activity yet.</div>
                @endforelse
            </div>
        </div>

        <div style="margin-top: 1rem;">
            {{ $activities->links() }}
        </div>
    </div>
</x-filament-panels::page>