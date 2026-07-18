@extends('layouts.guest')

@section('title', __('Confirm Password'))

@section('content')
    <x-auth.heading title="Confirm your password">
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Please confirm your password before continuing.
        </p>
    </x-auth.heading>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <x-form.field label="Password" for="password" required :error="$errors->first('password')">
            <x-form.password-input id="password" name="password" required autofocus autocomplete="current-password" :error="$errors->has('password')" />
        </x-form.field>

        <x-ui.button type="submit" variant="primary" style="solid" class="w-full">Confirm</x-ui.button>
    </form>
@endsection
