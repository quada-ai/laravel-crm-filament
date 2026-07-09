<x-filament-panels::page>
    @include('laravel-crm-filament::partials.crm-card-styles')

    @php
        $activities = $this->getActivities();
        $tabs = ['all', 'notes', 'tasks', 'calls', 'meetings', 'lunches', 'files'];
        $scopeBase = 'inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition';
        $scopeActive = 'bg-primary-600 text-white';
        $scopeInactive = 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10';
        $tabBase = 'inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium transition';
        $tabActive = 'bg-primary-600 text-white';
        $tabInactive = 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10';
    @endphp

    <div class="crm-activity-feed" data-testid="crm-activity-feed">
        <div class="crm-activity-scope flex flex-wrap gap-2 mb-4" data-testid="crm-activity-scope">
            <button
                type="button"
                wire:click="setScope('mine')"
                class="{{ $scopeBase }} {{ $scope === 'mine' ? $scopeActive : $scopeInactive }}"
            >
                {{ __('laravel-crm-filament::labels.sales.my_activity') }}
            </button>
            <button
                type="button"
                wire:click="setScope('all')"
                class="{{ $scopeBase }} {{ $scope === 'all' ? $scopeActive : $scopeInactive }}"
            >
                {{ __('laravel-crm-filament::labels.sales.all_activity') }}
            </button>
        </div>

        <div class="crm-activity-tabs flex flex-wrap gap-2 mb-4" data-testid="crm-activity-tabs">
            @foreach ($tabs as $t)
                <button
                    type="button"
                    wire:click="setTab('{{ $t }}')"
                    class="{{ $tabBase }} {{ $tab === $t ? $tabActive : $tabInactive }}"
                    data-tab="{{ $t }}"
                >
                    {{ ucfirst($t) }}
                </button>
            @endforeach
        </div>

        <div class="crm-timeline" data-testid="crm-timeline">
            @forelse ($activities as $activity)
                @include('laravel-crm-filament::crm-activity', ['activity' => $activity, 'last' => $loop->last])
            @empty
                <div class="crm-card-empty">No activity yet.</div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    </div>
</x-filament-panels::page>
