@extends('layouts.app')

@section('title', __('Dashboard'))

@section('header')
    <x-ui.page-header title="Dashboard">
        @if($isAdmin)
            Overview of your application's users, roles, and activity.
        @else
            Welcome back, {{ auth()->user()->name }}.
        @endif
    </x-ui.page-header>
@endsection

@section('content')
    @if($isAdmin)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat-card label="Total Users" :value="$totalUsers" variant="primary">
                <x-slot:icon><x-icon.users class="h-5 w-5" /></x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card label="Active Users" :value="$activeUsers" variant="success">
                <x-slot:icon><x-icon.check-circle class="h-5 w-5" /></x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card label="Roles" :value="$totalRoles" variant="info">
                <x-slot:icon><x-icon.identification class="h-5 w-5" /></x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card label="Permissions" :value="$totalPermissions" variant="secondary">
                <x-slot:icon><x-icon.lock-closed class="h-5 w-5" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <x-ui.card-section title="Recent Activity" description="The most recent actions recorded across the application." class="mt-6">
            @if($recentActivity->isEmpty())
                <x-ui.empty-state :bordered="false" title="No activity yet" description="Activity will show up here once users start signing in and admins start managing the app." />
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($recentActivity as $entry)
                        <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="truncate text-sm text-gray-900 dark:text-gray-100">{{ $entry['message'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $entry['causer_name'] }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500">{{ $entry['created_at_label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card-section>
    @else
        {{-- Demo figures — wire these up to real metrics when this dashboard gets real content. --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat-card label="Total Revenue" value="$48,900" change="+12.4%" trend="up" variant="success">
                <x-slot:icon><x-icon.chart-bar class="h-5 w-5" /></x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card label="New Customers" value="1,204" change="+5.2%" trend="up" variant="primary">
                <x-slot:icon><x-icon.users class="h-5 w-5" /></x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card label="Active Orders" value="86" change="-2.1%" trend="down" variant="warning">
                <x-slot:icon><x-icon.squares-2x2 class="h-5 w-5" /></x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card label="Satisfaction" value="4.8 / 5" change="+0.3" trend="up" variant="info">
                <x-slot:icon><x-icon.star class="h-5 w-5" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <x-ui.empty-state title="Your dashboard content goes here." class="mt-6">
            <x-slot:icon><x-icon.squares-2x2 class="h-6 w-6" /></x-slot:icon>
        </x-ui.empty-state>
    @endif
@endsection
