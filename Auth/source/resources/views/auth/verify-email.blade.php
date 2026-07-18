@extends('layouts.guest')

@section('title', __('Verify Email'))

@section('content')
    <div class="mx-auto mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400">
        <x-icon.information-circle class="h-6 w-6" />
    </div>

    <x-auth.heading title="Verify your email">
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Thanks for signing up! Before getting started, please verify your email address by clicking the link we just emailed to you. If you didn't receive it, we'll gladly send you another.
        </p>
    </x-auth.heading>

    @if (session('status') == 'verification-link-sent')
        <x-ui.alert variant="success" :icon="false" class="mb-4">
            A new verification link has been sent to the email address you provided during registration.
        </x-ui.alert>
    @endif

    <div class="flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.button type="submit" variant="primary" style="solid" class="w-full">Resend Verification Email</x-ui.button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary" style="outline" class="w-full">Log Out</x-ui.button>
        </form>
    </div>
@endsection
