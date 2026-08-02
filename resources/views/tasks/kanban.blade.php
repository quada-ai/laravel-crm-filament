<x-filament-panels::page>
    @php
        $byStatus = $this->getTasksByStatus();
        $columns = [
            'open' => ['label' => 'Open', 'color' => '#6b7280'],
            'today' => ['label' => 'Today', 'color' => '#f59e0b'],
            'overdue' => ['label' => 'Overdue', 'color' => '#ef4444'],
            'completed' => ['label' => 'Completed', 'color' => '#10b981'],
        ];
    @endphp

    <style>
        .crm-task-kanban {
            display: grid;
            grid-template-columns: repeat(4, minmax(260px, 1fr));
            gap: 1rem;
            overflow-x: auto;
        }
        .crm-task-col {
            background: rgba(0,0,0,0.04);
            border-radius: 0.75rem;
            padding: 0.75rem;
            min-height: 200px;
        }
        html.dark .crm-task-col { background: rgba(255,255,255,0.04); }
        .crm-task-col-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 0.75rem;
        }
        .crm-task-col-title { font-weight: 600; font-size: 0.875rem; }
        .crm-task-col-count { font-size: 0.75rem; color: #6b7280; }
        .crm-task-list { display: flex; flex-direction: column; gap: 0.5rem; min-height: 60px; }
        .crm-task-card {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 0.5rem;
            padding: 0.625rem 0.75rem;
            cursor: grab;
            transition: box-shadow 120ms ease;
        }
        html.dark .crm-task-card { background: rgb(31,41,55); border-color: rgba(255,255,255,0.08); }
        .crm-task-card:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
        .crm-task-card-link { display: block; text-decoration: none; color: inherit; }
        .crm-task-card-title { font-size: 0.875rem; font-weight: 500; }
        .crm-task-card-meta { font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem; }
        .crm-task-dot {
            display:inline-block; width:0.5rem; height:0.5rem;
            border-radius:9999px; margin-right:0.375rem; vertical-align:middle;
        }
    </style>

    <div
        x-data="{
            initSortables() {
                if (typeof window.Sortable === 'undefined') return;
                this.$refs.board.querySelectorAll('[data-task-column]').forEach(col => {
                    new window.Sortable(col, {
                        group: 'crm-tasks',
                        animation: 150,
                        ghostClass: 'opacity-50',
                        onEnd: (evt) => {
                            const status = evt.to.getAttribute('data-status');
                            const taskId = evt.item.getAttribute('data-task-id');
                            if (status && taskId) {
                                $wire.moveTask(taskId, status);
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
        <div x-ref="board" class="crm-task-kanban">
            @foreach ($columns as $status => $meta)
                @php $tasks = $byStatus[$status] ?? collect(); @endphp
                <div class="crm-task-col">
                    <div class="crm-task-col-head">
                        <div class="crm-task-col-title">
                            <span class="crm-task-dot" style="background: {{ $meta['color'] }};"></span>
                            {{ $meta['label'] }}
                        </div>
                        <div class="crm-task-col-count">{{ $tasks->count() }}</div>
                    </div>
                    <div data-task-column data-status="{{ $status }}" class="crm-task-list">
                        @foreach ($tasks as $task)
                            <div data-task-id="{{ $task->external_id }}" class="crm-task-card">
                                <a href="{{ $this::getResource()::getUrl('edit', ['record' => $task->external_id]) }}"
                                   class="crm-task-card-link">
                                    <div class="crm-task-card-title">{{ $task->name }}</div>
                                    @if ($task->due_at)
                                        <div class="crm-task-card-meta">Due {{ $task->due_at->format('M j, Y') }}</div>
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
