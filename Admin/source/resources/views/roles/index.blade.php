@extends('layouts.app')

@section('title', 'Roles')

@section('header')
    <x-ui.page-header title="Roles">
        Create roles and control which permissions they grant. The <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-gray-800">admin</code>
        and <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-gray-800">user</code> roles are protected — they can be edited but not deleted.
    </x-ui.page-header>
@endsection

@section('content')
    @php
        $emptyForm = ['name' => '', 'display_name' => '', 'permission_ids' => []];
    @endphp

    <div
        class="ui-card"
        x-data="dataTable(@js($roles), {
            searchKeys: ['name', 'display_name'],
            sortKey: 'name',
            perPage: 10,
            emptyForm: @js($emptyForm),
            entityLabel: 'role',
            rowLabel: (row) => row.display_name,
            onSubmit: (form, mode) => fetch(mode === 'add' ? '{{ route('admin.roles.store') }}' : `{{ url('/admin/roles') }}/${form.id}`, {
                method: mode === 'add' ? 'POST' : 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(form),
            }).then(async (response) => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message ?? Object.values(data.errors ?? {}).flat()[0] ?? 'Failed to save role.');
                }
                if (mode === 'add') {
                    // Rows are rendered once by Blade at page load, so a brand-new role has no
                    // matching <tr> to reveal — reload once the toast has had a moment to show.
                    setTimeout(() => window.location.reload(), 600);
                }
                return data;
            }),
            onDelete: (row) => fetch(`{{ url('/admin/roles') }}/${row.id}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            }).then(async (response) => {
                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    throw new Error(data.message ?? 'Failed to delete role.');
                }
            }),
        })"
    >
        <div class="flex flex-col gap-4 border-b border-gray-200 p-4 sm:p-6 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <x-ui.table.search x-model.debounce.300ms="search" placeholder="Search roles..." />

            @can('roles.create')
                <x-ui.button variant="primary" @click="openAddModal()">
                    <x-slot:iconLeft><x-icon.plus /></x-slot:iconLeft>
                    Add Role
                </x-ui.button>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-gray-50 dark:bg-white/[0.03]">
                    <tr>
                        <x-ui.table.sort-th sort-key="name">Role</x-ui.table.sort-th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Display Name</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400"># Permissions</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400"># Users</th>
                        <th scope="col" class="px-4 py-3.5 text-right text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Actions</th>
                    </tr>
                </thead>

                <tbody x-ref="tbody" class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($roles as $role)
                        <tr
                            data-row-id="{{ $role['id'] }}"
                            x-data="{
                                menuOpen: false,
                                get row() {
                                    return rows.find((r) => r.id === {{ $role['id'] }}) ?? {};
                                },
                            }"
                            x-show="visibleIds.includes({{ $role['id'] }})"
                            x-cloak
                            @table-actions-menu-toggle="menuOpen = $event.detail.open"
                            :class="menuOpen ? 'bg-gray-50 dark:bg-white/[0.02]' : ''"
                            class="transition hover:bg-gray-50 dark:hover:bg-white/[0.02]"
                        >
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $role['name'] }}</span>
                                    @if($role['is_protected'])
                                        <x-ui.badge variant="warning" size="sm">Default</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300" x-text="row.display_name">{{ $role['display_name'] }}</td>
                            <td class="px-4 py-4"><x-ui.badge variant="primary" size="sm" x-text="row.permissions_count">{{ $role['permissions_count'] }}</x-ui.badge></td>
                            <td class="px-4 py-4"><x-ui.badge variant="primary" size="sm" x-text="row.users_count">{{ $role['users_count'] }}</x-ui.badge></td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex">
                                    <x-ui.table.actions-menu>
                                        @can('roles.edit')
                                            <x-ui.table.actions-menu-item @click="openEditModal(row)">
                                                <x-slot:icon><x-icon.pencil class="h-4 w-4" /></x-slot:icon>
                                                Edit
                                            </x-ui.table.actions-menu-item>
                                        @endcan
                                        @can('roles.delete')
                                            <template x-if="!row.is_protected">
                                                <x-ui.table.actions-menu-item variant="danger" @click="confirmDelete(row)">
                                                    <x-slot:icon><x-icon.trash class="h-4 w-4" /></x-slot:icon>
                                                    Delete
                                                </x-ui.table.actions-menu-item>
                                            </template>
                                        @endcan
                                    </x-ui.table.actions-menu>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    <tr x-show="totalCount === 0">
                        <td colspan="5" class="px-4 py-6">
                            <x-ui.empty-state :bordered="false">
                                <x-slot:icon><x-icon.search class="h-6 w-6" /></x-slot:icon>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">No roles found</p>
                                <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">No roles match "<span x-text="search"></span>".</p>
                            </x-ui.empty-state>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 p-4 sm:p-6 dark:border-gray-800">
            <x-ui.table.pagination item="roles" />
        </div>

        {{--
            Add/Edit modal's permission-group state lives in its own nested (not spread)
            x-data, sibling to the outer dataTable() scope: object-spreading dataTable()'s
            return value into one merged literal would evaluate all of its `get ...()`
            computed properties (visibleIds, sortedRows, etc.) once and bake in static
            values, permanently freezing search/sort/pagination. Nesting instead keeps
            those getters live while this scope still reads/writes the parent's `form` via
            Alpine's scope chain. It's scoped to just the modal (not the whole card) so
            <tbody x-ref="tbody"> above stays owned by the outer component — $refs is
            scoped to the nearest x-data, so sort's reorderRows() couldn't find it otherwise.
        --}}
        <div
            x-data="{
                permissionGroups: @js($permissionGroups),
                totalPermissions: {{ $totalPermissions }},
                permissionSearch: '',

                get filteredPermissionGroups() {
                    const query = this.permissionSearch.trim().toLowerCase();
                    if (!query) return this.permissionGroups;

                    return this.permissionGroups
                        .map((group) => ({
                            label: group.label,
                            permissions: group.permissions.filter((permission) =>
                                permission.name.toLowerCase().includes(query) || permission.display_name.toLowerCase().includes(query)
                            ),
                        }))
                        .filter((group) => group.permissions.length > 0);
                },

                groupPermissionIds(group) {
                    return group.permissions.map((permission) => permission.id);
                },
                groupSelectedCount(group) {
                    return this.groupPermissionIds(group).filter((id) => this.hasPermission(id)).length;
                },
                isGroupFullySelected(group) {
                    const ids = this.groupPermissionIds(group);
                    return ids.length > 0 && ids.every((id) => this.hasPermission(id));
                },
                isGroupPartiallySelected(group) {
                    return this.groupSelectedCount(group) > 0 && !this.isGroupFullySelected(group);
                },
                toggleGroup(group) {
                    const ids = this.groupPermissionIds(group);
                    this.form.permission_ids = this.isGroupFullySelected(group)
                        ? this.form.permission_ids.filter((id) => !ids.includes(id))
                        : [...new Set([...this.form.permission_ids, ...ids])];
                },
                hasPermission(id) {
                    return this.form.permission_ids.includes(id);
                },
                togglePermission(id) {
                    this.form.permission_ids = this.hasPermission(id)
                        ? this.form.permission_ids.filter((permissionId) => permissionId !== id)
                        : [...this.form.permission_ids, id];
                },
                isAllSelected() {
                    return this.totalPermissions > 0 && this.form.permission_ids.length === this.totalPermissions;
                },
                isAllPartiallySelected() {
                    return this.form.permission_ids.length > 0 && !this.isAllSelected();
                },
                toggleAll() {
                    this.form.permission_ids = this.isAllSelected()
                        ? []
                        : this.permissionGroups.flatMap((group) => this.groupPermissionIds(group));
                },
            }"
        >
            {{-- Add / Edit modal — one form, reused for both by toggling `formMode`. --}}
            <x-ui.modal show="showFormModal" :close-on-backdrop="false" max-width="lg" :title="null">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="formMode === 'add' ? 'Add Role' : 'Edit Role'"></h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="formMode === 'add' ? 'Create a new role and grant it permissions.' : 'Update the display name and permissions for this role.'"></p>

                <form x-ref="roleForm" class="mt-4 space-y-5" @submit.prevent="submitForm()">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.field label="Role Name" for="role_name" required hint="Lowercase letters, numbers, and hyphens only.">
                            <x-form.input
                                id="role_name"
                                placeholder="e.g. super-admin"
                                x-model="form.name"
                                x-bind:disabled="formMode === 'edit'"
                                class="disabled:cursor-not-allowed disabled:opacity-60"
                                required
                                pattern="[a-z0-9\-]+"
                                title="Lowercase letters, numbers, and hyphens only."
                            />
                        </x-form.field>

                        <x-form.field label="Display Name" for="role_display_name" required>
                            <x-form.input id="role_display_name" placeholder="e.g. Super Admin" x-model="form.display_name" required />
                        </x-form.field>
                    </div>

                    <div>
                        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                    <x-icon.lock-closed class="h-4 w-4" />
                                </span>
                                <div>
                                    <p class="flex items-center gap-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        Permissions
                                        <span class="font-normal text-brand-600 dark:text-brand-400" x-text="'(' + form.permission_ids.length + '/' + totalPermissions + ')'"></span>
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">Select what this role can access</p>
                                </div>
                            </div>

                            <div class="w-full sm:ml-auto sm:w-64">
                                <x-ui.table.search x-model.debounce.150ms="permissionSearch" placeholder="Search permissions..." />
                            </div>
                        </div>

                        <div class="scrollbar-thin max-h-72 space-y-2 overflow-y-auto rounded-control border border-gray-200 p-2 dark:border-gray-800">
                            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-control bg-gray-50 px-3.5 py-3 dark:bg-white/[0.03]">
                                <span class="flex items-center gap-3">
                                    <input type="checkbox" x-bind:checked="isAllSelected()" @change="toggleAll()" class="sr-only" />
                                    <x-form.checkbox-box checked="isAllSelected()" indeterminate="isAllPartiallySelected()" />
                                    <span class="text-sm">
                                        <span class="block font-semibold text-gray-900 dark:text-gray-100">Select All Permissions</span>
                                        <span class="block text-xs text-gray-400 dark:text-gray-500">Grant full access to all features</span>
                                    </span>
                                </span>
                                <span class="shrink-0 text-sm text-gray-400 dark:text-gray-500" x-text="totalPermissions + ' permissions'"></span>
                            </label>

                            <template x-for="group in filteredPermissionGroups" :key="group.label">
                                <div x-data="{ open: true }" class="rounded-control border border-gray-200 dark:border-gray-800">
                                    <div
                                        role="button"
                                        tabindex="0"
                                        @click="open = !open"
                                        @keydown.enter.prevent="open = !open"
                                        @keydown.space.prevent="open = !open"
                                        class="flex cursor-pointer items-center justify-between gap-3 px-3.5 py-3 focus:outline-none"
                                    >
                                        <span class="flex flex-1 items-center gap-3">
                                            <label class="flex items-center" @click.stop>
                                                <input type="checkbox" x-bind:checked="isGroupFullySelected(group)" @change="toggleGroup(group)" class="sr-only" />
                                                <x-form.checkbox-box checked="isGroupFullySelected(group)" indeterminate="isGroupPartiallySelected(group)" />
                                            </label>
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="group.label"></span>
                                        </span>

                                        <span class="flex shrink-0 items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                                            <span x-text="groupSelectedCount(group) + '/' + group.permissions.length + ' permissions'"></span>
                                            <x-icon.chevron-down class="h-4 w-4 shrink-0 transition-transform duration-150" x-bind:class="open && 'rotate-180'" />
                                        </span>
                                    </div>

                                    <div
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"
                                        class="grid gap-1.5 border-t border-gray-200 p-3 sm:grid-cols-2 dark:border-gray-800"
                                    >
                                        <template x-for="permission in group.permissions" :key="permission.id">
                                            <label class="flex cursor-pointer items-center gap-2.5 rounded-control px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                                                <input type="checkbox" x-bind:checked="hasPermission(permission.id)" @change="togglePermission(permission.id)" class="sr-only" />
                                                <x-form.checkbox-box checked="hasPermission(permission.id)" />
                                                <span class="text-gray-700 dark:text-gray-300" x-text="permission.display_name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <p class="px-3 py-6 text-center text-sm text-gray-400 dark:text-gray-500" x-show="filteredPermissionGroups.length === 0">No permissions match your search.</p>
                        </div>
                    </div>
                </form>

                <x-slot:footer>
                    <x-ui.button type="button" variant="secondary" style="outline" @click="showFormModal = false">Cancel</x-ui.button>
                    <x-ui.button type="button" variant="primary" x-bind:disabled="submitting" @click="$refs.roleForm.reportValidity() && submitForm()" x-text="submitting ? 'Saving…' : (formMode === 'add' ? 'Add Role' : 'Save Changes')"></x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>
    </div>
@endsection
