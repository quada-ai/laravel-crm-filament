<x-filament-panels::page>
    {{-- Avatar + Account + Notification preferences --}}
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">
                {{ __('laravel-crm-filament::labels.actions.save') }}
            </x-filament::button>
        </div>
    </form>

    {{-- Update password --}}
    <form wire:submit="updatePassword" class="space-y-6">
        {{ $this->passwordForm }}

        <div class="flex justify-end">
            <x-filament::button type="submit" color="primary" wire:loading.attr="disabled" wire:target="updatePassword">
                {{ __('laravel-crm-filament::labels.actions.update_password') }}
            </x-filament::button>
        </div>
    </form>

    {{-- Two-factor authentication --}}
    <x-filament::section data-section="two-factor">
        <x-slot name="heading">
            {{ __('laravel-crm-filament::labels.sections.two_factor_authentication') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Add additional security to your account using two-factor authentication.') }}
        </x-slot>

        @php($enabled = $this->isTwoFactorEnabled())
        @php($pending = $this->isTwoFactorPending())
        @php($columnsPresent = $this->hasTwoFactorColumns())

        @if (! $columnsPresent)
            <p class="text-sm text-gray-500">
                {{ __('Two-factor authentication is not configured for this installation.') }}
            </p>
        @elseif ($enabled)
            <p class="text-sm text-gray-700 dark:text-gray-300" data-2fa-state="enabled">
                {{ __('You have enabled two factor authentication.') }}
            </p>
        @elseif ($pending)
            <p class="text-sm text-gray-700 dark:text-gray-300" data-2fa-state="pending">
                {{ __('Finish enabling two factor authentication.') }}
            </p>
        @else
            <p class="text-sm text-gray-700 dark:text-gray-300" data-2fa-state="disabled">
                {{ __('You have not enabled two factor authentication.') }}
            </p>
        @endif

        @if ($columnsPresent && ($showingQrCode || $showingRecoveryCodes || $enabled || $pending))
            @php($qr = $this->getQrCodeSvg())
            @php($setupKey = $this->getSetupKey())
            @php($recoveryCodes = $this->getRecoveryCodes())

            @if (($showingQrCode || $pending) && $qr)
                <div class="mt-4 space-y-3" data-2fa-block="qr-code">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Scan the QR code with your authenticator app or use the setup key below.') }}
                    </p>

                    @if (Str::startsWith($qr, 'data:image'))
                        <img src="{{ $qr }}" alt="2FA QR code" class="h-48 w-48" data-2fa="qr-image">
                    @elseif (Str::startsWith($qr, '<svg') || Str::startsWith($qr, '<?xml'))
                        <div class="h-48 w-48" data-2fa="qr-svg">{!! $qr !!}</div>
                    @else
                        <a href="{{ $qr }}" target="_blank" class="text-primary-600 underline" data-2fa="qr-url">
                            {{ $qr }}
                        </a>
                    @endif

                    @if ($setupKey)
                        <p class="text-sm text-gray-600">
                            {{ __('Setup key') }}: <code class="font-mono text-xs">{{ $setupKey }}</code>
                        </p>
                    @endif
                </div>
            @endif

            @if ($confirmingTwoFactor)
                <div class="mt-4 space-y-2" data-2fa-block="confirm">
                    <label for="twoFactorCode" class="block text-sm font-medium">{{ __('Code') }}</label>
                    <input type="text" id="twoFactorCode" wire:model.live="twoFactorCode"
                           class="fi-input block w-full max-w-xs rounded-lg border bg-white px-3 py-1.5 text-base shadow-sm">
                    @error('twoFactorCode')
                        <p class="text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            @if ($showingRecoveryCodes && ! empty($recoveryCodes))
                <div class="mt-4 space-y-2" data-2fa-block="recovery-codes">
                    <p class="text-sm font-medium">{{ __('Recovery codes') }}</p>
                    <p class="text-xs text-gray-600">
                        {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.') }}
                    </p>
                    <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-800">
                        <ul class="space-y-1 font-mono text-xs">
                            @foreach ($recoveryCodes as $code)
                                <li>{{ $code }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endif

        <div class="mt-4 flex flex-wrap gap-2">
            @if ($columnsPresent && ! $enabled && ! $pending)
                <x-filament::button
                    type="button"
                    wire:click="enableTwoFactor"
                    wire:loading.attr="disabled"
                    wire:target="enableTwoFactor"
                    data-2fa-action="enable"
                >
                    {{ __('laravel-crm-filament::labels.actions.enable_two_factor') }}
                </x-filament::button>
            @endif

            @if ($columnsPresent && $confirmingTwoFactor)
                <x-filament::button
                    type="button"
                    color="primary"
                    wire:click="confirmTwoFactor"
                    wire:loading.attr="disabled"
                    wire:target="confirmTwoFactor"
                    data-2fa-action="confirm"
                >
                    {{ __('Confirm') }}
                </x-filament::button>
            @endif

            @if ($columnsPresent && ($enabled || $pending))
                <x-filament::button
                    type="button"
                    color="gray"
                    wire:click="regenerateRecoveryCodes"
                    wire:target="regenerateRecoveryCodes"
                    data-2fa-action="regenerate-codes"
                >
                    {{ __('Regenerate recovery codes') }}
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    wire:click="showRecoveryCodes"
                    wire:target="showRecoveryCodes"
                    data-2fa-action="show-codes"
                >
                    {{ __('Show recovery codes') }}
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="danger"
                    wire:click="disableTwoFactor"
                    wire:target="disableTwoFactor"
                    data-2fa-action="disable"
                >
                    {{ __('laravel-crm-filament::labels.actions.disable_two_factor') }}
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>

    {{-- Browser sessions --}}
    <x-filament::section data-section="browser-sessions">
        <x-slot name="heading">
            {{ __('laravel-crm-filament::labels.sections.browser_sessions') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Manage and log out your active sessions on other browsers and devices.') }}
        </x-slot>

        @php($sessions = $this->getSessions())

        @if ($sessions->isEmpty())
            <p class="text-sm text-gray-500">
                {{ __('Browser sessions are only listed when the application uses the database session driver.') }}
            </p>
        @else
            <ul class="space-y-3" data-sessions-list>
                @foreach ($sessions as $session)
                    <li class="flex items-start gap-3 border-b pb-3 last:border-b-0">
                        <div class="text-2xl">
                            @if ($session->agent['is_desktop'])
                                <span aria-hidden="true">&#x1F5A5;&#xFE0F;</span>
                            @else
                                <span aria-hidden="true">&#x1F4F1;</span>
                            @endif
                        </div>
                        <div class="text-sm">
                            <div>
                                {{ $session->agent['platform'] }} &mdash; {{ $session->agent['browser'] }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $session->ip_address }}
                                @if ($session->is_current_device)
                                    &middot; <span class="font-medium text-success-700">{{ __('This device') }}</span>
                                @else
                                    &middot; {{ __('Last active') }} {{ $session->last_active }}
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-4 space-y-2">
            <label for="logoutPassword" class="block text-sm font-medium">
                {{ __('laravel-crm-filament::labels.fields.current_password') }}
            </label>
            <input type="password" id="logoutPassword" wire:model.live="logoutPassword"
                   class="fi-input block w-full max-w-xs rounded-lg border bg-white px-3 py-1.5 text-base shadow-sm">
            @error('logoutPassword')
                <p class="text-sm text-danger-600">{{ $message }}</p>
            @enderror

            <x-filament::button
                type="button"
                color="danger"
                wire:click="logoutOtherBrowserSessions"
                wire:loading.attr="disabled"
                wire:target="logoutOtherBrowserSessions"
                data-action="logout-other-sessions"
            >
                {{ __('laravel-crm-filament::labels.actions.log_out_other_sessions') }}
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
