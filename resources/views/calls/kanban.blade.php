<x-filament-panels::page>
    @php
        $byStatus = $this->getCallsByStatus();
        $columns = [
            'planned' => ['label' => 'Planned', 'color' => '#f59e0b'],
            'done' => ['label' => 'Done', 'color' => '#10b981'],
        ];
    @endphp

    <style>
        .crm-call-kanban {
            display: grid;
            grid-template-columns: repeat(2, minmax(260px, 1fr));
            gap: 1rem;
            overflow-x: auto;
        }
        .crm-call-col {
            background: rgba(0,0,0,0.04);
            border-radius: 0.75rem;
            padding: 0.75rem;
            min-height: 200px;
        }
        html.dark .crm-call-col { background: rgba(255,255,255,0.04); }
        .crm-call-col-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 0.75rem;
        }
        .crm-call-col-title { font-weight: 600; font-size: 0.875rem; }
        .crm-call-col-count { font-size: 0.75rem; color: #6b7280; }
        .crm-call-list { display: flex; flex-direction: column; gap: 0.5rem; min-height: 60px; }
        .crm-call-card {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 0.5rem;
            padding: 0.625rem 0.75rem;
            cursor: grab;
            transition: box-shadow 120ms ease;
        }
        html.dark .crm-call-card { background: rgb(31,41,55); border-color: rgba(255,255,255,0.08); }
        .crm-call-card:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
        .crm-call-card-link { display: block; text-decoration: none; color: inherit; }
        .crm-call-card-title { font-size: 0.875rem; font-weight: 500; }
        .crm-call-card-meta { font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem; }
        .crm-call-dot {
            display:inline-block; width:0.5rem; height:0.5rem;
            border-radius:9999px; margin-right:0.375rem; vertical-align:middle;
        }
    </style>

    <div
        x-data="{
            initSortables() {
                if (typeof window.Sortable === 'undefined') return;
                this.$refs.board.querySelectorAll('[data-call-column]').forEach(col => {
                    new window.Sortable(col, {
                        group: 'crm-calls',
                        animation: 150,
                        ghostClass: 'opacity-50',
                        onEnd: (evt) => {
                            const status = evt.to.getAttribute('data-status');
                            const callId = evt.item.getAttribute('data-call-id');
                            if (status && callId) {
                                $wire.moveCall(callId, status);
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
        <div x-ref="board" class="crm-call-kanban">
            @foreach ($columns as $status => $meta)
                @php $calls = $byStatus[$status] ?? collect(); @endphp
                <div class="crm-call-col">
                    <div class="crm-call-col-head">
                        <div class="crm-call-col-title">
                            <span class="crm-call-dot" style="background: {{ $meta['color'] }};"></span>
                            {{ $meta['label'] }}
                        </div>
                        <div class="crm-call-col-count">{{ $calls->count() }}</div>
                    </div>
                    <div data-call-column data-status="{{ $status }}" class="crm-call-list">
                        @foreach ($calls as $call)
                            <div data-call-id="{{ $call->external_id }}" class="crm-call-card">
                                <a href="{{ route('filament.crm.resources.calls.view', ['record' => $call->external_id]) }}"
                                   class="crm-call-card-link">
                                    <div class="crm-call-card-title">{{ $call->name }}</div>
                                    @if ($call->start_at)
                                        <div class="crm-call-card-meta">{{ $call->start_at->format('M j, Y H:i') }}</div>
                                    @endif
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
