<x-filament-panels::page>
    @php
        /** @var \VentureDrake\LaravelCrm\Models\ChatConversation $record */
        $record = $this->record;
        $messages = $this->messageItems ?? collect();
        $statusLabels = [
            'open' => __('laravel-crm-filament::labels.chat.status_open'),
            'pending' => __('laravel-crm-filament::labels.chat.status_pending'),
            'closed' => __('laravel-crm-filament::labels.chat.status_closed'),
        ];
        $statusLabel = $statusLabels[$record->status] ?? $record->status;
    @endphp

    <style>
        .crm-chat {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        html.dark .crm-chat { background: rgb(17,24,39); border-color: rgba(255,255,255,0.08); }
        .crm-chat-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        html.dark .crm-chat-head { border-color: rgba(255,255,255,0.08); }
        .crm-chat-head-name { font-weight: 600; font-size: 0.875rem; }
        .crm-chat-head-email { font-size: 0.75rem; color: #6b7280; }
        .crm-chat-head-meta { font-size: 0.75rem; color: #6b7280; }
        .crm-chat-body {
            padding: 1rem 1.25rem;
            max-height: 60vh;
            overflow-y: auto;
            display: flex; flex-direction: column; gap: 0.75rem;
        }
        .crm-chat-row { display: flex; }
        .crm-chat-row.from-agent { justify-content: flex-end; }
        .crm-chat-row.from-visitor { justify-content: flex-start; }
        .crm-chat-row.from-system { justify-content: center; }
        .crm-chat-bubble {
            max-width: 75%;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.35;
        }
        .crm-chat-bubble.agent { background: #05b3a9; color: #fff; }
        .crm-chat-bubble.visitor { background: rgba(0,0,0,0.05); color: #111827; }
        html.dark .crm-chat-bubble.visitor { background: rgba(255,255,255,0.06); color: #f3f4f6; }
        .crm-chat-bubble.system {
            font-style: italic; font-size: 0.75rem;
            background: rgba(0,0,0,0.04); color: #4b5563;
        }
        html.dark .crm-chat-bubble.system { background: rgba(255,255,255,0.04); color: #9ca3af; }
        .crm-chat-sender { font-size: 0.65rem; font-weight: 600; opacity: 0.8; margin-bottom: 0.125rem; }
        .crm-chat-body-text { white-space: pre-wrap; word-break: break-word; }
        .crm-chat-time { font-size: 0.65rem; opacity: 0.65; margin-top: 0.25rem; text-align: right; }
        .crm-chat-empty { text-align: center; color: #6b7280; padding: 2rem 1rem; font-size: 0.875rem; }
        .crm-chat-reply {
            border-top: 1px solid rgba(0,0,0,0.06);
            padding: 0.75rem 1.25rem 1rem;
            display: flex; flex-direction: column; gap: 0.5rem;
        }
        html.dark .crm-chat-reply { border-color: rgba(255,255,255,0.08); }
        .crm-chat-reply-row { display: flex; align-items: flex-end; gap: 0.5rem; }
        .crm-chat-reply-textarea {
            flex: 1;
            min-height: 70px;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(0,0,0,0.12);
            background: #fff;
            color: #111827;
            font-size: 0.875rem;
            line-height: 1.4;
            resize: vertical;
        }
        html.dark .crm-chat-reply-textarea {
            background: rgb(31,41,55); color: #f3f4f6; border-color: rgba(255,255,255,0.12);
        }
        .crm-chat-send-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background: #05b3a9;
            color: #fff;
            font-size: 0.875rem;
            font-weight: 600;
            border: 0;
            cursor: pointer;
        }
        .crm-chat-send-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .crm-chat-reply-error { color: #b91c1c; font-size: 0.75rem; }
        .crm-chat-status-pill {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .crm-chat-status-pill.open { background: #d1fae5; color: #065f46; }
        .crm-chat-status-pill.pending { background: #fef3c7; color: #92400e; }
        .crm-chat-status-pill.closed { background: #e5e7eb; color: #374151; }
        .crm-chat-closed-banner {
            border-top: 1px solid rgba(0,0,0,0.06);
            padding: 0.75rem 1.25rem;
            text-align: center;
            color: #6b7280;
            font-size: 0.75rem;
        }
    </style>

    <div class="crm-chat" data-testid="crm-chat-thread">
        <div class="crm-chat-head">
            <div>
                <div class="crm-chat-head-name">{{ $record->visitor?->name ?? 'Anonymous visitor' }}</div>
                @if ($record->visitor?->email)
                    <div class="crm-chat-head-email">{{ $record->visitor->email }}</div>
                @endif
            </div>
            <div class="crm-chat-head-meta">
                {{ $record->chat_id }}
                <span class="crm-chat-status-pill {{ $record->status }}">{{ $statusLabel }}</span>
            </div>
        </div>

        <div class="crm-chat-body" data-testid="crm-chat-body">
            @forelse ($messages as $message)
                @php
                    $fromAgent = $message->sender_type === 'user';
                    $fromSystem = $message->sender_type === 'system';
                    $rowClass = $fromSystem ? 'from-system' : ($fromAgent ? 'from-agent' : 'from-visitor');
                    $bubbleClass = $fromSystem ? 'system' : ($fromAgent ? 'agent' : 'visitor');
                @endphp
                <div class="crm-chat-row {{ $rowClass }}" data-message-id="{{ $message->id }}" data-sender="{{ $message->sender_type }}">
                    <div class="crm-chat-bubble {{ $bubbleClass }}">
                        @unless ($fromSystem)
                            <div class="crm-chat-sender">{{ $message->senderName() }}</div>
                        @endunless
                        <div class="crm-chat-body-text">{{ $message->body }}</div>
                        <div class="crm-chat-time" title="{{ $message->created_at?->toDateTimeString() }}">
                            {{ $message->created_at?->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="crm-chat-empty">{{ __('laravel-crm-filament::labels.chat.no_messages') }}</div>
            @endforelse
        </div>

        @if ($record->status === 'closed')
            <div class="crm-chat-closed-banner">
                {{ __('laravel-crm-filament::labels.chat.status_closed') }}
            </div>
        @else
            <form class="crm-chat-reply" wire:submit.prevent="sendInlineReply" data-testid="crm-chat-reply-form">
                <div class="crm-chat-reply-row">
                    <textarea
                        class="crm-chat-reply-textarea"
                        wire:model="replyBody"
                        rows="3"
                        placeholder="{{ __('laravel-crm-filament::labels.actions.reply') }}…"
                    ></textarea>
                    <button
                        type="submit"
                        class="crm-chat-send-btn"
                        wire:loading.attr="disabled"
                    >{{ __('laravel-crm-filament::labels.actions.send') }}</button>
                </div>
                @error('replyBody')
                    <div class="crm-chat-reply-error">{{ $message }}</div>
                @enderror
            </form>
        @endif
    </div>
</x-filament-panels::page>
