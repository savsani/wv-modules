@extends('layouts.guest')

@section('title', __('Log in'))

@section('content')
    <x-auth.heading title="Welcome back">
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Log in to your account to continue.</p>
    </x-auth.heading>

    @php
        $status = session('status');
        $isDeactivated = $status === 'account-deactivated';
    @endphp

    <x-auth.session-status
        class="mb-4"
        :variant="$isDeactivated ? 'danger' : 'success'"
        :status="$isDeactivated ? __('Your account is not active. Please contact an administrator.') : $status"
    />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-form.field label="Email" for="email" required :error="$errors->first('email')">
            <x-form.input id="email" type="email" name="email" placeholder="Enter your email" :value="old('email')" required autofocus autocomplete="username" :error="$errors->has('email')" />
        </x-form.field>

        <x-form.field label="Password" for="password" required :error="$errors->first('password')">
            @if (Route::has('password.request'))
                <x-slot:action>
                    <x-auth.text-link href="{{ route('password.request') }}">
                        Forgot password?
                    </x-auth.text-link>
                </x-slot:action>
            @endif
            <x-form.password-input id="password" name="password" required autocomplete="current-password" :error="$errors->has('password')" />
        </x-form.field>

        <x-form.checkbox id="remember" name="remember">Remember me</x-form.checkbox>

        <x-ui.button type="submit" variant="primary" style="solid" class="w-full">Log in</x-ui.button>
    </form>

    @if (Route::has('register'))
        <x-auth.footer-link question="Don't have an account?" href="{{ route('register') }}">Sign up</x-auth.footer-link>
    @endif
@endsection
