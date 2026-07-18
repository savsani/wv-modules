@extends('layouts.app')

@section('title', 'Activity Log')

@section('header')
    <x-ui.page-header title="Activity Log">
        A record of notable authentication and administrative events across the application. Showing the most
        recent {{ number_format($maxRows) }} entries.
    </x-ui.page-header>
@endsection

@section('content')
    {{-- Outer scope holds only the Browser/UA modal's state — the Changes
         (before/after) modal below reuses dataTable()'s own built-in
         viewRow/showViewModal instead, since this page has no per-row
         edit/delete flow of its own to otherwise use them for. Keeping the UA
         modal's state up here (rather than spread into dataTable()'s return
         value, which silently freezes its getters — see roles.md §14.1)
         lets both the row buttons (nested inside dataTable()'s scope) and
         this modal (a sibling of the card) read/write it via Alpine's normal
         ancestor scope chaining. --}}
    <div x-data="{ showUaModal: false, selectedUserAgent: '' }">
        <div
            class="ui-card"
            x-data="dataTable(@js($logs), {
                searchKeys: ['user_name', 'user_email', 'message', 'ip_address'],
                sortKey: 'created_at_ts',
                sortDir: 'desc',
                perPage: 20,
            })"
        >
        <div class="flex flex-col gap-4 border-b border-gray-200 p-4 sm:p-6 dark:border-gray-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <x-ui.table.search x-model.debounce.300ms="search" placeholder="Search logs..." />

                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.table.filter
                        label="Log Type"
                        placeholder="All Types"
                        :options="$logTypeOptions"
                        @change="setFilter('log_type', $event.target.value)"
                    />

                    <x-ui.table.filter
                        label="User"
                        placeholder="All Users"
                        :options="$users"
                        @change="setFilter('user_id', $event.target.value)"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">From</span>
                        <div
                            class="w-36"
                            @change="setFilter('created_at_ts_min', $event.detail.value ? new Date($event.detail.value + 'T00:00:00').getTime() : null)"
                        >
                            <x-form.date-picker placeholder="dd/mm/yyyy" />
                        </div>

                        <span class="text-sm text-gray-500 dark:text-gray-400">To</span>
                        <div
                            class="w-36"
                            @change="setFilter('created_at_ts_max', $event.detail.value ? new Date($event.detail.value + 'T23:59:59').getTime() : null)"
                        >
                            <x-form.date-picker placeholder="dd/mm/yyyy" />
                        </div>
                    </div>

                    @can('activity-log.clear')
                        <x-navigation.dropdown align="right" width="64">
                            <x-slot:trigger>
                                <x-ui.button variant="danger" style="outline" size="md">
                                    <x-slot:iconLeft><x-icon.trash class="h-4 w-4" /></x-slot:iconLeft>
                                    Clear Logs
                                </x-ui.button>
                            </x-slot:trigger>

                            <x-slot:content>
                                <button
                                    type="button"
                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                    @click="$store.confirmDialog.open({
                                        title: 'Clear logs older than 1 week?',
                                        message: 'This will permanently delete every activity log entry older than 7 days. This action cannot be undone.',
                                        variant: 'warning',
                                        confirmText: 'Clear',
                                        onConfirm: () => fetch('{{ route('admin.activity-log.clear') }}', {
                                            method: 'DELETE',
                                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                            body: JSON.stringify({ scope: '7_days' }),
                                        }).then(async (response) => {
                                            const data = await response.json().catch(() => ({}));
                                            if (!response.ok) {
                                                $store.toast.show({ message: data.message ?? 'Failed to clear logs.', type: 'danger' });
                                                return;
                                            }
                                            $store.toast.show({ message: data.message, type: 'success' });
                                            setTimeout(() => window.location.reload(), 600);
                                        }),
                                    })"
                                >
                                    Older than 1 week
                                </button>

                                <button
                                    type="button"
                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                    @click="$store.confirmDialog.open({
                                        title: 'Clear logs older than 30 days?',
                                        message: 'This will permanently delete every activity log entry older than 30 days. This action cannot be undone.',
                                        variant: 'warning',
                                        confirmText: 'Clear',
                                        onConfirm: () => fetch('{{ route('admin.activity-log.clear') }}', {
                                            method: 'DELETE',
                                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                            body: JSON.stringify({ scope: '30_days' }),
                                        }).then(async (response) => {
                                            const data = await response.json().catch(() => ({}));
                                            if (!response.ok) {
                                                $store.toast.show({ message: data.message ?? 'Failed to clear logs.', type: 'danger' });
                                                return;
                                            }
                                            $store.toast.show({ message: data.message, type: 'success' });
                                            setTimeout(() => window.location.reload(), 600);
                                        }),
                                    })"
                                >
                                    Older than 30 days
                                </button>

                                <button
                                    type="button"
                                    class="block w-full px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                                    @click="$store.confirmDialog.open({
                                        title: 'Clear all activity logs?',
                                        message: 'This will permanently delete every activity log entry, with no way to undo it.',
                                        variant: 'danger',
                                        confirmText: 'Clear All',
                                        onConfirm: () => fetch('{{ route('admin.activity-log.clear') }}', {
                                            method: 'DELETE',
                                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                            body: JSON.stringify({ scope: 'all' }),
                                        }).then(async (response) => {
                                            const data = await response.json().catch(() => ({}));
                                            if (!response.ok) {
                                                $store.toast.show({ message: data.message ?? 'Failed to clear logs.', type: 'danger' });
                                                return;
                                            }
                                            $store.toast.show({ message: data.message, type: 'success' });
                                            setTimeout(() => window.location.reload(), 600);
                                        }),
                                    })"
                                >
                                    Clear all logs
                                </button>
                            </x-slot:content>
                        </x-navigation.dropdown>
                    @endcan
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
                <thead class="bg-gray-50 dark:bg-white/[0.03]">
                    <tr>
                        <x-ui.table.sort-th sort-key="created_at_ts">Date &amp; Time</x-ui.table.sort-th>
                        <x-ui.table.sort-th sort-key="user_name">User</x-ui.table.sort-th>
                        <x-ui.table.sort-th sort-key="event_label">Event Type</x-ui.table.sort-th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Message</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">IP Address</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Browser / UA</th>
                    </tr>
                </thead>

                <tbody x-ref="tbody" class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($logs as $log)
                        <tr
                            data-row-id="{{ $log['id'] }}"
                            x-show="visibleIds.includes({{ $log['id'] }})"
                            x-cloak
                            class="transition hover:bg-gray-50 dark:hover:bg-white/[0.02]"
                        >
                            <td class="px-4 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $log['created_at_label'] }}</td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $log['user_name'] }}</div>
                                @if($log['user_email'])
                                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ $log['user_email'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <x-ui.badge variant="{{ $log['log_type_variant'] }}" size="sm">{{ $log['log_type_label'] }}</x-ui.badge>
                                <div
                                    class="mt-1 text-xs font-medium {{ match($log['event_label_variant']) {
                                        'success' => 'text-green-600 dark:text-green-400',
                                        'danger' => 'text-red-600 dark:text-red-400',
                                        default => 'text-gray-500 dark:text-gray-400',
                                    } }}"
                                >{{ $log['event_label'] }}</div>
                            </td>
                            <td class="max-w-xs px-4 py-4 text-gray-700 dark:text-gray-300">
                                {{ $log['message'] }}
                                @if($log['properties'])
                                    <button
                                        type="button"
                                        @click="openViewModal({ event_label: @js($log['event_label']), properties: @js($log['properties']) })"
                                        class="ml-1 inline-flex align-text-bottom text-gray-400 transition hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                        aria-label="View changes"
                                        title="View changes"
                                    >
                                        <x-icon.code-view class="h-4 w-4" />
                                    </button>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $log['ip_address'] }}</td>
                            <td class="px-4 py-4">
                                @if($log['user_agent'])
                                    <button
                                        type="button"
                                        @click="showUaModal = true; selectedUserAgent = @js($log['user_agent'])"
                                        class="text-gray-400 transition hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                        aria-label="View user agent"
                                    >
                                        <x-icon.information-circle class="h-5 w-5" />
                                    </button>
                                @else
                                    <span class="text-gray-300 dark:text-gray-700">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    <tr x-show="totalCount === 0">
                        <td colspan="6" class="px-4 py-6">
                            <x-ui.empty-state :bordered="false">
                                <x-slot:icon><x-icon.search class="h-6 w-6" /></x-slot:icon>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">No activity found</p>
                                <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">No log entries match the current filters.</p>
                            </x-ui.empty-state>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 p-4 sm:p-6 dark:border-gray-800">
            <x-ui.table.pagination item="log entries" />
        </div>

        {{-- Changes modal — reuses dataTable()'s built-in view-modal state (viewRow/showViewModal); this page has no per-row edit/delete flow of its own to otherwise use them for. Shows the before/after JSON diff captured on role/user update events. --}}
        <x-ui.modal show="showViewModal" max-width="2xl" :title="null">
            <template x-if="viewRow">
                <div>
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                            <x-icon.code-view class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="viewRow.event_label + ' — Changes'"></h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Field values before and after this update.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Before</p>
                            <pre class="scrollbar-thin mt-1 max-h-72 overflow-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-700 dark:bg-white/5 dark:text-gray-300" x-text="JSON.stringify(viewRow.properties?.before ?? {}, null, 2)"></pre>
                        </div>
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">After</p>
                            <pre class="scrollbar-thin mt-1 max-h-72 overflow-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-700 dark:bg-white/5 dark:text-gray-300" x-text="JSON.stringify(viewRow.properties?.after ?? {}, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </template>

            <x-slot:footer>
                <x-ui.button type="button" variant="secondary" style="outline" @click="showViewModal = false">Close</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
        </div>

        {{-- Browser/UA modal — state lives on the outer wrapper (see comment above), since this button needs to be reachable independently of the Changes modal above, which already occupies dataTable()'s single built-in view-modal slot. --}}
        <x-ui.modal show="showUaModal" max-width="md" :title="null">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                    <x-icon.browser-window class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Browser / User Agent</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full user agent string for this activity entry.</p>
                </div>
            </div>

            <p class="mt-4 rounded-lg bg-gray-50 p-4 text-sm break-words text-gray-700 dark:bg-white/5 dark:text-gray-300" x-text="selectedUserAgent"></p>

            <x-slot:footer>
                <x-ui.button type="button" variant="secondary" style="outline" @click="showUaModal = false">Close</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
@endsection
