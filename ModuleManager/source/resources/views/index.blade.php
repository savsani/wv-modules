@extends('layouts.app')

@section('title', 'Module Manager')

@section('header')
    <x-ui.page-header title="Module Manager">
        Install, update, and inspect the app's Wv modules — the web equivalent of <code>php artisan wv:list</code>.
    </x-ui.page-header>
@endsection

@section('content')
    <div
        class="ui-card"
        x-data="{
            ...dataTable(@js($modules), {
                searchKeys: ['name', 'key', 'description'],
                sortKey: 'name',
                perPage: 20,
            }),
            operations: {},
            operationFor(key) {
                return this.operations[key] ?? null;
            },
            triggerInstall(row) {
                this.$store.confirmDialog.open({
                    title: `Install ${row.name}?`,
                    message: row.missing_dependencies.length
                        ? `This will also install its dependencies: ${row.missing_dependencies.join(', ')}.`
                        : `Fetch and install the &quot;${row.name}&quot; module.`,
                    variant: 'primary',
                    confirmText: 'Install',
                    onConfirm: () => this.runOperation(row, 'install'),
                });
            },
            triggerUpdate(row) {
                this.$store.confirmDialog.open({
                    title: `Update ${row.name}?`,
                    message: `This overwrites the &quot;${row.name}&quot; module directory completely, including any local edits.`,
                    variant: 'warning',
                    confirmText: 'Update',
                    onConfirm: () => this.runOperation(row, 'update'),
                });
            },
            triggerMigrate(row) {
                this.$store.confirmDialog.open({
                    title: 'Run pending migrations?',
                    message: `This runs &quot;php artisan migrate --force&quot; against the production database. Review the module's migrations before continuing.`,
                    variant: 'warning',
                    confirmText: 'Run migrations',
                    onConfirm: () => this.runOperation(row, 'migrate'),
                });
            },
            async runOperation(row, action) {
                try {
                    const response = await fetch(`{{ url('/admin/module-manager') }}/${row.key}/${action}`, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message ?? `Failed to ${action} &quot;${row.name}&quot;.`);
                    }

                    this.operations[row.key] = data;
                    this.pollOperation(row.key, data.id);
                } catch (error) {
                    this.$store.toast.show({ message: error?.message ?? `Failed to ${action} &quot;${row.name}&quot;.`, type: 'danger' });
                }
            },
            pollOperation(key, operationId) {
                const poll = async () => {
                    const response = await fetch(`{{ url('/admin/module-manager/operations') }}/${operationId}`, {
                        headers: { Accept: 'application/json' },
                    });
                    const data = await response.json();
                    this.operations[key] = data;

                    if (data.status === 'succeeded' || data.status === 'failed') {
                        this.$store.toast.show({
                            message: data.status === 'succeeded'
                                ? `${key} ${data.action} succeeded.`
                                : (data.error_message ?? `${key} ${data.action} failed.`),
                            type: data.status === 'succeeded' ? 'success' : 'danger',
                        });

                        if (data.status === 'succeeded' && data.action !== 'migrate') {
                            setTimeout(() => window.location.reload(), 1500);
                        }

                        return;
                    }

                    setTimeout(poll, 2000);
                };

                poll();
            },
        }"
        x-init="rows.forEach((row) => { if (row.active_operation) { operations[row.key] = row.active_operation; pollOperation(row.key, row.active_operation.id); } })"
    >
        <div class="flex flex-col gap-4 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:border-gray-800">
            <x-ui.table.search x-model.debounce.300ms="search" placeholder="Search modules..." />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="bg-gray-50 dark:bg-white/[0.03]">
                    <tr>
                        <x-ui.table.sort-th sort-key="name">Module</x-ui.table.sort-th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Depends on</th>
                        <x-ui.table.sort-th sort-key="installed_version">Installed</x-ui.table.sort-th>
                        <x-ui.table.sort-th sort-key="latest_version">Latest</x-ui.table.sort-th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Status</th>
                        <th scope="col" class="px-4 py-3.5 text-right text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Actions</th>
                    </tr>
                </thead>

                <tbody x-ref="tbody" class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($modules as $module)
                        <tr
                            data-row-id="{{ $module['key'] }}"
                            x-show="visibleIds.includes('{{ $module['key'] }}')"
                            x-cloak
                            class="transition hover:bg-gray-50 dark:hover:bg-white/[0.02]"
                        >
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $module['name'] }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $module['description'] }}</div>
                            </td>
                            <td class="px-4 py-4 text-gray-500 dark:text-gray-400">
                                {{ $module['depends_on'] === [] ? '—' : implode(', ', $module['depends_on']) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ $module['installed_version'] ?? '—' }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ $module['latest_version'] ?? 'unknown' }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap" x-data="{ row: @js($module) }">
                                <template x-if="!operationFor(row.key) || ['succeeded', 'failed'].includes(operationFor(row.key)?.status)">
                                    @if (! $module['is_installed'])
                                        <x-ui.badge variant="secondary" size="sm">Not installed</x-ui.badge>
                                    @elseif ($module['update_available'])
                                        <x-ui.badge variant="warning" size="sm">Update available</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="success" size="sm">Up to date</x-ui.badge>
                                    @endif
                                </template>
                                <template x-if="operationFor(row.key) && !['succeeded', 'failed'].includes(operationFor(row.key)?.status)">
                                    <x-ui.badge variant="info" size="sm" x-text="operationFor(row.key).status === 'pending' ? 'Queued…' : `${operationFor(row.key).action}ing…`"></x-ui.badge>
                                </template>

                                <template x-if="operationFor(row.key)?.status === 'failed'">
                                    <p class="mt-1 max-w-xs text-xs text-red-600 dark:text-red-400" x-text="operationFor(row.key).error_message"></p>
                                </template>

                                <template x-if="operationFor(row.key)?.status === 'succeeded' && operationFor(row.key)?.action !== 'migrate'">
                                    <div class="mt-1">
                                        <button type="button" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400" @click="triggerMigrate(row)">
                                            Run pending migrations
                                        </button>
                                    </div>
                                </template>
                            </td>
                            <td class="px-4 py-4 text-right" x-data="{ row: @js($module) }">
                                <div class="inline-flex items-center gap-2">
                                    @if (! $module['is_installed'])
                                        @can('modules.install')
                                            <x-ui.button
                                                size="sm"
                                                variant="primary"
                                                x-bind:disabled="operationFor(row.key) && !['succeeded', 'failed'].includes(operationFor(row.key)?.status)"
                                                @click="triggerInstall(row)"
                                            >
                                                Install
                                            </x-ui.button>
                                        @endcan
                                    @else
                                        @can('modules.update')
                                            <x-ui.button
                                                size="sm"
                                                variant="secondary"
                                                style="outline"
                                                x-bind:disabled="operationFor(row.key) && !['succeeded', 'failed'].includes(operationFor(row.key)?.status)"
                                                @click="triggerUpdate(row)"
                                            >
                                                Update
                                            </x-ui.button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    <tr x-show="totalCount === 0">
                        <td colspan="6" class="px-4 py-6">
                            <x-ui.empty-state :bordered="false">
                                <x-slot:icon><x-icon.search class="h-6 w-6" /></x-slot:icon>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">No modules found</p>
                                <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">No modules match "<span x-text="search"></span>".</p>
                            </x-ui.empty-state>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 p-4 sm:p-6 dark:border-gray-800">
            <x-ui.table.pagination item="modules" />
        </div>
    </div>
@endsection
