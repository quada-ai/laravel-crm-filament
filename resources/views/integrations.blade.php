<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        @if ($this->xeroIsConnectedForView())
            <div>
                <x-filament::button type="submit">{{ __('laravel-crm-filament::labels.actions.save_sync_settings') }}</x-filament::button>
            </div>
        @endif
    </form>
</x-filament-panels::page>
