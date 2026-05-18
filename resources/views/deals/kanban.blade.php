<x-filament-panels::page>
    @php
        $stages = $this->getStages();
        $dealsByStage = $this->getDealsByStage();
    @endphp

    <div
        x-data="{
            initSortables() {
                if (typeof window.Sortable === 'undefined') return;
                this.$refs.board.querySelectorAll('[data-kanban-column]').forEach(col => {
                    new window.Sortable(col, {
                        group: 'crm-deals',
                        animation: 150,
                        ghostClass: 'opacity-50',
                        onEnd: (evt) => {
                            const stageId = evt.to.getAttribute('data-stage-id');
                            const dealId  = evt.item.getAttribute('data-deal-id');
                            if (stageId && dealId) {
                                $wire.moveDeal(dealId, parseInt(stageId));
                            }
                        },
                    });
                });
            },
        }"
        x-init="
            if (typeof window.Sortable === 'undefined') {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
                s.onload = () => initSortables();
                document.head.appendChild(s);
            } else {
                initSortables();
            }
        "
    >
        <div
            x-ref="board"
            class="grid gap-4 overflow-x-auto"
            style="grid-template-columns: repeat({{ max($stages->count(), 1) }}, minmax(260px, 1fr));"
        >
            @forelse ($stages as $stage)
                <div class="fi-section rounded-xl bg-gray-50 dark:bg-gray-900/40 p-3 min-h-[200px]">
                    <div class="flex items-center justify-between mb-3">
                        <div class="font-semibold text-sm">{{ $stage->name }}</div>
                        <div class="text-xs text-gray-500">
                            {{ ($dealsByStage[$stage->id] ?? collect())->count() }}
                        </div>
                    </div>
                    <div
                        data-kanban-column
                        data-stage-id="{{ $stage->id }}"
                        class="space-y-2 min-h-[60px]"
                    >
                        @foreach ($dealsByStage[$stage->id] ?? [] as $deal)
                            <div
                                data-deal-id="{{ $deal->external_id }}"
                                class="group relative rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-3 cursor-grab hover:shadow-md transition"
                            >
                                <div class="absolute top-1.5 right-1.5 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                                    <button type="button" title="Mark won"
                                            wire:click.stop="markWon('{{ $deal->external_id }}')"
                                            class="text-[10px] px-1.5 py-0.5 rounded bg-success-500 hover:bg-success-600 text-white">Won</button>
                                    <button type="button" title="Mark lost"
                                            wire:click.stop="markLost('{{ $deal->external_id }}')"
                                            class="text-[10px] px-1.5 py-0.5 rounded bg-danger-500 hover:bg-danger-600 text-white">Lost</button>
                                </div>
                                <a href="{{ route('filament.crm.resources.deals.edit', ['record' => $deal->external_id]) }}" class="block">
                                <div class="text-xs font-semibold text-gray-500">{{ $deal->deal_id }}</div>
                                <div class="text-sm font-medium text-gray-950 dark:text-white mt-0.5">{{ $deal->title }}</div>
                                @if ($deal->amount)
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ ($deal->amount / 100) }} {{ $deal->currency }}
                                    </div>
                                @endif
                                @if ($deal->expected_close)
                                    <div class="text-[10px] text-gray-400 mt-1">
                                        Closes {{ \Carbon\Carbon::parse($deal->expected_close)->format('M j') }}
                                    </div>
                                @endif
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-span-full text-sm text-gray-500 text-center py-12">
                    No pipeline stages configured for Deals.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
