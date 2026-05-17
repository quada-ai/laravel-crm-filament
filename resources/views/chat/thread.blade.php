<x-filament-panels::page>
    @php
        /** @var \VentureDrake\LaravelCrm\Models\ChatConversation $record */
        $record = $this->record;
        $messages = $record->messages()->orderBy('created_at')->get();
    @endphp

    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $record->visitor?->name ?? 'Anonymous visitor' }}
                </div>
                @if ($record->visitor?->email)
                    <div class="text-xs text-gray-500">{{ $record->visitor->email }}</div>
                @endif
            </div>
            <div class="text-xs text-gray-500">
                {{ $record->chat_id }} · {{ $record->status }}
            </div>
        </div>

        <div class="px-6 py-4 space-y-3 max-h-[60vh] overflow-y-auto">
            @forelse ($messages as $message)
                @php
                    $fromAgent = $message->sender_type === 'user';
                    $fromSystem = $message->sender_type === 'system';
                @endphp
                <div class="flex {{ $fromAgent ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%] rounded-lg px-3 py-2 text-sm
                        @if ($fromAgent) bg-primary-600 text-white
                        @elseif ($fromSystem) bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 italic
                        @else bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100
                        @endif
                    ">
                        @unless ($fromSystem)
                            <div class="text-[10px] font-semibold opacity-75 mb-0.5">
                                {{ $message->senderName() }}
                            </div>
                        @endunless
                        <div class="whitespace-pre-wrap">{{ $message->body }}</div>
                        <div class="text-[10px] opacity-60 mt-1 text-right">
                            {{ $message->created_at?->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-sm text-gray-500 text-center py-8">No messages yet.</div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
