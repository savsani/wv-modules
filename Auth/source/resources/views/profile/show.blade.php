@extends('layouts.app')

@section('title', __('Profile'))

@section('header')
    <x-ui.page-header title="Profile">
        Manage your account details, password, and security settings.
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        @if (\Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::updateProfileInformation()))
            @include('profile.partials.update-profile-information-form')
        @endif

        @if (\Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::updatePasswords()))
            @include('profile.partials.update-password-form')
        @endif

        @if (\Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::twoFactorAuthentication()))
            @include('profile.partials.two-factor-authentication-form')
        @endif
    </div>
@endsection
