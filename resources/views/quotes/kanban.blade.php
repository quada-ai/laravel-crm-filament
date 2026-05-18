<x-filament-panels::page>
    @php
        $stages = $this->getStages();
        $quotesByStage = $this->getQuotesByStage();
    @endphp

    <div
        x-data="{
            initSortables() {
                if (typeof window.Sortable === 'undefined') return;
                this.$refs.board.querySelectorAll('[data-kanban-column]').forEach(col => {
                    new window.Sortable(col, {
                        group: 'crm-quotes',
                        animation: 150,
                        ghostClass: 'opacity-50',
                        onEnd: (evt) => {
                            const stageId = evt.to.getAttribute('data-stage-id');
                            const quoteId = evt.item.getAttribute('data-quote-id');
                            if (stageId && quoteId) {
                                $wire.moveQuote(quoteId, parseInt(stageId));
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
                            {{ ($quotesByStage[$stage->id] ?? collect())->count() }}
                        </div>
                    </div>
                    <div
                        data-kanban-column
                        data-stage-id="{{ $stage->id }}"
                        class="space-y-2 min-h-[60px]"
                    >
                        @foreach ($quotesByStage[$stage->id] ?? [] as $quote)
                            <a
                                href="{{ route('filament.crm.resources.quotes.edit', ['record' => $quote->external_id]) }}"
                                data-quote-id="{{ $quote->external_id }}"
                                class="block rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-3 cursor-grab hover:shadow-md transition"
                            >
                                <div class="text-xs font-semibold text-gray-500">{{ $quote->quote_id }}</div>
                                <div class="text-sm font-medium text-gray-950 dark:text-white mt-0.5">{{ $quote->title }}</div>
                                @if ($quote->total)
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ ($quote->total / 100) }} {{ $quote->currency }}
                                    </div>
                                @endif
                                @if ($quote->expire_at)
                                    <div class="text-[10px] text-gray-400 mt-1">
                                        Expires {{ \Carbon\Carbon::parse($quote->expire_at)->format('M j') }}
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-span-full text-sm text-gray-500 text-center py-12">
                    No pipeline stages configured for Quotes.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
