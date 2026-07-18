@extends('layouts.guest')

@section('title', __('Reset Password'))

@section('content')
    <x-auth.heading title="Reset your password">
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter your new password below.</p>
    </x-auth.heading>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-form.field label="Email" for="email" required :error="$errors->first('email')">
            <x-form.input id="email" type="email" name="email" placeholder="Enter your email" :value="old('email', $request->email)" required autofocus autocomplete="username" :error="$errors->has('email')" />
        </x-form.field>

        <x-form.field label="New Password" for="password" required :error="$errors->first('password')">
            <x-form.password-input id="password" name="password" placeholder="Enter your new password" required autocomplete="new-password" :error="$errors->has('password')" />
        </x-form.field>

        <x-form.field label="Confirm New Password" for="password_confirmation" required :error="$errors->first('password_confirmation')">
            <x-form.password-input id="password_confirmation" name="password_confirmation" placeholder="Confirm your new password" required autocomplete="new-password" :error="$errors->has('password_confirmation')" />
        </x-form.field>

        <x-ui.button type="submit" variant="primary" style="solid" class="w-full">Reset Password</x-ui.button>
    </form>
@endsection
