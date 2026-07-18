@php
    $user = auth()->user();
    $enabled = (bool) $user->two_factor_secret;
    $confirmed = (bool) $user->two_factor_confirmed_at;
@endphp

<x-ui.card-section :title="__('Two Factor Authentication')" :description="__('Add additional security to your account using two factor authentication.')">
    <div x-data="{ showRecoveryCodes: false }">
        @if ($enabled && $confirmed)
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Two factor authentication is enabled.
            </h3>
        @elseif ($enabled)
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Finish enabling two factor authentication.
            </h3>
        @else
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Two factor authentication is not enabled.
            </h3>
        @endif

        <p class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
            When two factor authentication is enabled, you'll be prompted for a secure, random token during login,
            which you can retrieve from your phone's authenticator application.
        </p>

        @if ($enabled && ! $confirmed)
            <div class="mt-4 max-w-xl space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Scan the QR code below with your phone's authenticator application, then enter the code it
                    generates to finish setting up two factor authentication.
                </p>

                <div class="inline-block rounded-lg bg-white p-3">
                    {!! $user->twoFactorQrCodeSvg() !!}
                </div>

                <p class="font-mono text-sm text-gray-600 dark:text-gray-400">
                    Setup Key: {{ $user->twoFactorSecretKey() }}
                </p>

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="max-w-xs space-y-3">
                    @csrf
                    <x-form.field
                        label="Code"
                        for="two-factor-code"
                        :error="$errors->confirmTwoFactorAuthentication->first('code')"
                    >
                        <x-form.input
                            id="two-factor-code"
                            name="code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            :error="$errors->confirmTwoFactorAuthentication->has('code')"
                            required
                            autofocus
                        />
                    </x-form.field>
                    <x-ui.button type="submit" variant="primary" style="solid">
                        Confirm
                    </x-ui.button>
                </form>
            </div>
        @elseif ($enabled)
            <div class="mt-4 max-w-xl space-y-3" x-show="showRecoveryCodes" x-cloak>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Store these recovery codes in a secure password manager. They can be used to recover access to
                    your account if your two factor authentication device is lost.
                </p>
                <div class="grid gap-1 rounded-lg bg-gray-50 px-4 py-4 font-mono text-sm dark:bg-gray-950">
                    @foreach ($user->recoveryCodes() as $code)
                        <div class="text-gray-700 dark:text-gray-300">{{ $code }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-5 flex flex-wrap items-center gap-3">
            @if (! $enabled)
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <x-ui.button type="submit" variant="primary" style="solid">
                        Enable
                    </x-ui.button>
                </form>
            @endif

            @if ($enabled && $confirmed)
                <x-ui.button type="button" variant="secondary" style="outline" @click="showRecoveryCodes = !showRecoveryCodes">
                    <span x-text="showRecoveryCodes ? 'Hide Recovery Codes' : 'Show Recovery Codes'"></span>
                </x-ui.button>

                <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" style="outline">
                        Regenerate Recovery Codes
                    </x-ui.button>
                </form>
            @endif

            @if ($enabled)
                <form method="POST" action="{{ route('two-factor.disable') }}" x-ref="disableTwoFactorForm">
                    @csrf
                    @method('DELETE')
                    @if (! $confirmed)
                        <input type="hidden" name="cancelled" value="1">
                        <x-ui.button type="submit" variant="danger" style="solid">
                            Cancel
                        </x-ui.button>
                    @else
                        <x-ui.button
                            type="button"
                            variant="danger"
                            style="solid"
                            @click="$store.confirmDialog.open({
                                title: 'Disable two-factor authentication?',
                                message: 'You will no longer be prompted for a verification code when signing in.',
                                variant: 'danger',
                                confirmText: 'Disable',
                                onConfirm: () => $refs.disableTwoFactorForm.submit(),
                            })"
                        >
                            Disable
                        </x-ui.button>
                    @endif
                </form>
            @endif
        </div>

        @if (session('status') === \Laravel\Fortify\Fortify::TWO_FACTOR_AUTHENTICATION_ENABLED)
            <div x-init="$store.toast.show({ message: 'Scan the QR code and enter the generated code to complete two factor authentication setup.', type: 'warning' })"></div>
        @elseif (session('status') === \Laravel\Fortify\Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED)
            <div x-init="$store.toast.show({ message: 'Two factor authentication enabled successfully.', type: 'success' })"></div>
        @elseif (session('status') === \Laravel\Fortify\Fortify::TWO_FACTOR_AUTHENTICATION_DISABLED)
            <div x-init="$store.toast.show({ message: 'Two factor authentication disabled.', type: 'warning' })"></div>
        @elseif (session('status') === 'two-factor-authentication-setup-cancelled')
            <div x-init="$store.toast.show({ message: 'Two factor authentication setup cancelled.', type: 'info' })"></div>
        @endif
    </div>
</x-ui.card-section>
