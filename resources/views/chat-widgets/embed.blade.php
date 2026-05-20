<x-filament-panels::page>
    @php
        $snippet = $this->getEmbedSnippet();
        $iframeUrl = $this->getIframeUrl();
        $widget = $this->record;
    @endphp

    <style>
        .lcf-embed-card {
            background: var(--gray-50, #f9fafb);
            border: 1px solid var(--gray-200, #e5e7eb);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .lcf-embed-card h2 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .lcf-embed-snippet {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.85rem;
            background: var(--gray-900, #111827);
            color: #f3f4f6;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            white-space: pre-wrap;
            word-break: break-all;
            display: block;
        }
        .lcf-copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            margin-top: 0.5rem;
            padding: 0.375rem 0.75rem;
            background: #05b3a9;
            color: #fff;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.375rem;
            border: 0;
            cursor: pointer;
        }
        .lcf-copy-btn:hover { background: #048a82; }
        .lcf-iframe-preview {
            width: 100%;
            min-height: 480px;
            background: #fff;
            border: 1px solid var(--gray-200, #e5e7eb);
            border-radius: 0.5rem;
        }
        .lcf-help {
            color: var(--gray-500, #6b7280);
            font-size: 0.8125rem;
            margin-top: 0.5rem;
        }
    </style>

    <div x-data="{
            copied: false,
            copy() {
                const text = this.$refs.snippet.innerText;
                navigator.clipboard.writeText(text).then(() => {
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 1500);
                });
            },
        }"
    >
        <div class="lcf-embed-card">
            <h2>Embed snippet</h2>
            <p class="lcf-help">
                Paste this <code>&lt;script&gt;</code> tag just before the closing
                <code>&lt;/body&gt;</code> tag on any page where you want the chat widget to appear.
            </p>
            <code x-ref="snippet" class="lcf-embed-snippet">{{ $snippet }}</code>
            <button type="button" class="lcf-copy-btn" @click="copy()">
                <span x-show="!copied">Copy to clipboard</span>
                <span x-show="copied" x-cloak>Copied!</span>
            </button>
        </div>

        <div class="lcf-embed-card">
            <h2>Live preview</h2>
            <p class="lcf-help">
                This is exactly what visitors to <strong>{{ $widget->name }}</strong> will see.
            </p>
            <iframe
                class="lcf-iframe-preview"
                src="{{ $iframeUrl }}"
                title="Chat widget preview"
                loading="lazy"
            ></iframe>
        </div>
    </div>
</x-filament-panels::page>
