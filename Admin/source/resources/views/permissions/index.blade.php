@extends('layouts.app')

@section('title', 'Permissions')

@section('header')
    <x-ui.page-header title="Permissions">
        Read-only catalog of every permission a role can be granted. Permissions are seeded by the application
        (and, later, registered by individual modules) — they can't be added, edited, or deleted from this page.
    </x-ui.page-header>
@endsection

@section('content')
    <div
        class="ui-card"
        x-data="dataTable(@js($permissions), {
            searchKeys: ['name', 'display_name'],
            sortKey: 'name',
            perPage: 'all',
        })"
    >
        <div class="border-b border-gray-200 p-4 sm:p-6 dark:border-gray-800">
            <x-ui.table.search x-model.debounce.300ms="search" placeholder="Search permissions..." />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-gray-50 dark:bg-white/[0.03]">
                    <tr>
                        <x-ui.table.sort-th sort-key="name">Permission Name</x-ui.table.sort-th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Display Name</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Module</th>
                        <x-ui.table.sort-th sort-key="roles_count"># Roles</x-ui.table.sort-th>
                    </tr>
                </thead>

                <tbody x-ref="tbody" class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($permissions as $permission)
                        <tr
                            data-row-id="{{ $permission['id'] }}"
                            x-show="visibleIds.includes({{ $permission['id'] }})"
                            x-cloak
                            class="transition hover:bg-gray-50 dark:hover:bg-white/[0.02]"
                        >
                            <td class="px-4 py-4 whitespace-nowrap">
                                <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $permission['name'] }}</code>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $permission['display_name'] }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $permission['module'] }}</td>
                            <td class="px-4 py-4">
                                <x-ui.badge variant="primary" size="sm">{{ $permission['roles_count'] }}</x-ui.badge>
                            </td>
                        </tr>
                    @endforeach

                    <tr x-show="totalCount === 0">
                        <td colspan="4" class="px-4 py-6">
                            <x-ui.empty-state :bordered="false">
                                <x-slot:icon><x-icon.search class="h-6 w-6" /></x-slot:icon>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">No permissions found</p>
                                <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">No permissions match "<span x-text="search"></span>".</p>
                            </x-ui.empty-state>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
