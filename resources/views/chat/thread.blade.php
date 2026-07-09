<x-filament-panels::page>
    @php
        /** @var \VentureDrake\LaravelCrm\Models\ChatConversation $record */
        $record = $this->record;
        $messages = $this->messageItems ?? collect();
        $visitor = $record->visitor;
        $pageViews = $visitor?->pageViews()->limit(20)->get() ?? collect();
    @endphp

    <style>
        .crm-chat-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 1024px) {
            .crm-chat-grid { grid-template-columns: 2fr 1fr; }
        }
        .crm-chat-card {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        html.dark .crm-chat-card { background: rgb(17,24,39); border-color: rgba(255,255,255,0.08); }
        .crm-chat-card-title { font-size: 1.125rem; font-weight: 600; padding: 1rem 1.25rem 0.5rem; }
        .crm-chat-card-body { padding: 0 1.25rem 1rem; }
        .crm-chat-body {
            padding: 1.25rem;
            max-height: 60vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .crm-chat-row { display: flex; }
        .crm-chat-row.from-agent { justify-content: flex-end; }
        .crm-chat-row.from-visitor { justify-content: flex-start; }
        .crm-chat-row.from-system { justify-content: center; }
        .crm-chat-bubble {
            max-width: 70%;
            border-radius: 1rem;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        .crm-chat-bubble.agent { background: #05b3a9; color: #fff; }
        .crm-chat-bubble.visitor { background: rgba(0,0,0,0.05); color: #111827; }
        html.dark .crm-chat-bubble.visitor { background: rgba(255,255,255,0.06); color: #f3f4f6; }
        .crm-chat-bubble.system {
            font-style: italic; font-size: 0.75rem;
            background: rgba(0,0,0,0.04); color: #4b5563;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
        }
        html.dark .crm-chat-bubble.system { background: rgba(255,255,255,0.04); color: #9ca3af; }
        .crm-chat-meta { font-size: 0.7rem; opacity: 0.75; margin-bottom: 0.25rem; }
        .crm-chat-body-text { white-space: pre-wrap; word-break: break-word; }
        .crm-chat-empty { text-align: center; color: #6b7280; padding: 2rem 1rem; font-size: 0.875rem; }
        .crm-chat-reply {
            border-top: 1px solid rgba(0,0,0,0.06);
            padding: 1rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        html.dark .crm-chat-reply { border-color: rgba(255,255,255,0.08); }
        .crm-chat-reply-textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.625rem 0.875rem;
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
        .crm-chat-reply-actions { display: flex; justify-content: flex-end; }
        .crm-chat-send-btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            background: #05b3a9;
            color: #fff;
            font-size: 0.875rem;
            font-weight: 600;
            border: 0;
            cursor: pointer;
            transition: background 0.15s;
        }
        .crm-chat-send-btn:hover { background: #04a099; }
        .crm-chat-send-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .crm-chat-send-btn svg { width: 1rem; height: 1rem; }
        .crm-chat-reply-error { color: #b91c1c; font-size: 0.75rem; }
        .crm-chat-closed-banner {
            border-top: 1px solid rgba(0,0,0,0.06);
            padding: 1rem 1.25rem;
            text-align: center;
            color: #6b7280;
            font-size: 0.8125rem;
        }
        html.dark .crm-chat-closed-banner { border-color: rgba(255,255,255,0.08); }
        .crm-chat-side { display: flex; flex-direction: column; gap: 1rem; }
        .crm-chat-dl {
            display: grid;
            grid-template-columns: minmax(0, auto) 1fr;
            column-gap: 1.5rem;
            row-gap: 0.625rem;
            font-size: 0.875rem;
        }
        .crm-chat-dl dt { color: #6b7280; font-weight: 400; }
        .crm-chat-dl dd { color: #111827; margin: 0; word-break: break-word; }
        html.dark .crm-chat-dl dd { color: #f3f4f6; }
        .crm-chat-dl dd.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.8125rem; }
        .crm-chat-empty-side { text-align: center; color: #6b7280; padding: 1.5rem 0; font-size: 0.875rem; }
        .crm-chat-page-list { list-style: none; padding: 0; margin: 0; }
        .crm-chat-page-list li {
            padding: 0.625rem 0;
            border-top: 1px solid rgba(0,0,0,0.06);
        }
        html.dark .crm-chat-page-list li { border-color: rgba(255,255,255,0.06); }
        .crm-chat-page-list li:first-child { border-top: 0; }
        .crm-chat-page-title { font-weight: 500; font-size: 0.8125rem; }
        .crm-chat-page-url {
            color: #6b7280;
            font-size: 0.75rem;
            word-break: break-all;
            display: block;
            margin-top: 0.125rem;
        }
        .crm-chat-page-url:hover { color: #05b3a9; }
        .crm-chat-page-time { font-size: 0.7rem; color: #9ca3af; margin-top: 0.25rem; }
    </style>

    <div class="crm-chat-grid" data-testid="crm-chat-thread">
        {{-- LEFT: chat thread + compose --}}
        <div class="crm-chat-card">
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
                                <div class="crm-chat-meta">
                                    {{ $message->senderName() }} &middot; {{ $message->created_at?->diffForHumans() }}
                                </div>
                            @endunless
                            <div class="crm-chat-body-text">{{ $message->body }}</div>
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
                    <textarea
                        class="crm-chat-reply-textarea"
                        wire:model="replyBody"
                        rows="3"
                        placeholder="{{ __('laravel-crm-filament::labels.chat.type_a_message') }}..."
                    ></textarea>
                    @error('replyBody')
                        <div class="crm-chat-reply-error">{{ $message }}</div>
                    @enderror
                    <div class="crm-chat-reply-actions">
                        <button
                            type="submit"
                            class="crm-chat-send-btn"
                            wire:loading.attr="disabled"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M3.105 3.105a.75.75 0 01.815-.164l14 5.25a.75.75 0 010 1.418l-14 5.25a.75.75 0 01-1-.815l1.586-4.756L10 10 4.507 8.812 2.92 4.056a.75.75 0 01.185-.951z" />
                            </svg>
                            {{ __('laravel-crm-filament::labels.actions.send') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>

        {{-- RIGHT: visitor + page history --}}
        <aside class="crm-chat-side">
            <div class="crm-chat-card">
                <div class="crm-chat-card-title">{{ __('laravel-crm-filament::labels.chat.visitor') }}</div>
                <div class="crm-chat-card-body">
                    <dl class="crm-chat-dl">
                        <dt>{{ __('laravel-crm-filament::labels.fields.name') }}</dt>
                        <dd>{{ $visitor?->name ?: '—' }}</dd>

                        <dt>{{ __('laravel-crm-filament::labels.fields.email') }}</dt>
                        <dd>{{ $visitor?->email ?: '—' }}</dd>

                        <dt>IP</dt>
                        <dd class="mono">{{ $visitor?->ip_address ?: '—' }}</dd>

                        <dt>{{ __('laravel-crm-filament::labels.fields.last_active') }}</dt>
                        <dd>{{ $visitor?->last_seen_at?->diffForHumans() ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="crm-chat-card">
                <div class="crm-chat-card-title">{{ __('laravel-crm-filament::labels.chat.page_history') }}</div>
                <div class="crm-chat-card-body">
                    @if ($pageViews->isEmpty())
                        <div class="crm-chat-empty-side">
                            {{ __('laravel-crm-filament::labels.chat.no_pages_viewed') }}
                        </div>
                    @else
                        <ul class="crm-chat-page-list">
                            @foreach ($pageViews as $view)
                                <li>
                                    <div class="crm-chat-page-title" title="{{ $view->title ?: '' }}">
                                        {{ $view->title ?: '(untitled)' }}
                                    </div>
                                    <a class="crm-chat-page-url" href="{{ $view->url }}" target="_blank" rel="noopener noreferrer" title="{{ $view->url }}">
                                        {{ $view->url }}
                                    </a>
                                    <div class="crm-chat-page-time">{{ $view->viewed_at?->diffForHumans() }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</x-filament-panels::page>
