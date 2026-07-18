@extends('layouts.guest')

@section('title', __('Forgot Password'))

@section('content')
    <x-auth.heading title="Forgot your password?">
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            We'll email you a password reset link.
        </p>
    </x-auth.heading>

    <x-auth.session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-form.field label="Email" for="email" required :error="$errors->first('email')">
            <x-form.input id="email" type="email" name="email" placeholder="Enter your email" :value="old('email')" required autofocus autocomplete="username" :error="$errors->has('email')" />
        </x-form.field>

        <x-ui.button type="submit" variant="primary" style="solid" class="w-full">Email Password Reset Link</x-ui.button>
    </form>

    <x-auth.footer-link question="Remembered your password?" href="{{ route('login') }}">Log in</x-auth.footer-link>
@endsection
