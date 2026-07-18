@extends('layouts.app')

@section('title', 'Module Manager')

@section('header')
    <x-ui.page-header title="Module Manager">
        Enable, disable, and manage the application's feature modules.
    </x-ui.page-header>
@endsection

@section('content')
    <div class="ui-card">
        <x-ui.empty-state title="Module management is coming soon" description="Tools to enable, disable, and configure nwidart/laravel-modules modules will land here in a future update.">
            <x-slot:icon><x-icon.module-manager class="h-6 w-6" /></x-slot:icon>
        </x-ui.empty-state>
    </div>
@endsection
