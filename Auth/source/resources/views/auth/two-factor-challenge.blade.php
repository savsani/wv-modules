@extends('layouts.guest')

@section('title', __('Two Factor Confirmation'))

@section('content')
    <div x-data="{ useRecoveryCode: false }">
        <x-auth.heading title="Two-factor authentication">
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-show="!useRecoveryCode">
                Please confirm access to your account by entering the authentication code provided by your authenticator application.
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-show="useRecoveryCode" x-cloak>
                Please confirm access to your account by entering one of your emergency recovery codes.
            </p>
        </x-auth.heading>

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf

            <x-form.field x-show="!useRecoveryCode" label="Code" for="code" :error="$errors->first('code')">
                <x-form.input id="code" name="code" placeholder="Enter authentication code" inputmode="numeric" autocomplete="one-time-code" autofocus x-bind:required="!useRecoveryCode" :error="$errors->has('code')" />
            </x-form.field>

            <x-form.field x-show="useRecoveryCode" x-cloak label="Recovery Code" for="recovery_code" :error="$errors->first('recovery_code')">
                <x-form.input id="recovery_code" name="recovery_code" placeholder="Enter recovery code" autocomplete="one-time-code" x-bind:required="useRecoveryCode" :error="$errors->has('recovery_code')" />
            </x-form.field>

            <x-ui.button type="submit" variant="primary" style="solid" class="w-full">Log in</x-ui.button>

            <x-auth.text-link class="w-full text-center text-sm" @click="useRecoveryCode = !useRecoveryCode">
                <span x-show="!useRecoveryCode">Use a recovery code</span>
                <span x-show="useRecoveryCode" x-cloak>Use an authentication code</span>
            </x-auth.text-link>
        </form>
    </div>
@endsection
