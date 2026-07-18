@extends('layouts.guest')

@section('title', __('Register'))

@section('content')
    <x-auth.heading title="Create an account">
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fill in the details below to get started</p>
    </x-auth.heading>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <x-form.field label="Name" for="name" required :error="$errors->first('name')">
            <x-form.input id="name" name="name" placeholder="Enter your name" :value="old('name')" required autofocus autocomplete="name" :error="$errors->has('name')" />
        </x-form.field>

        <x-form.field label="Email" for="email" required :error="$errors->first('email')">
            <x-form.input id="email" type="email" name="email" placeholder="Enter your email" :value="old('email')" required autocomplete="username" :error="$errors->has('email')" />
        </x-form.field>

        <x-form.field label="Password" for="password" required :error="$errors->first('password')">
            <x-form.password-input id="password" name="password" required autocomplete="new-password" :error="$errors->has('password')" />
        </x-form.field>

        <x-form.field label="Confirm Password" for="password_confirmation" required :error="$errors->first('password_confirmation')">
            <x-form.password-input id="password_confirmation" name="password_confirmation" placeholder="Confirm your password" required autocomplete="new-password" :error="$errors->has('password_confirmation')" />
        </x-form.field>

        <x-ui.button type="submit" variant="primary" style="solid" class="w-full">Register</x-ui.button>
    </form>

    <x-auth.footer-link question="Already have an account?" href="{{ route('login') }}">Log in</x-auth.footer-link>
@endsection
