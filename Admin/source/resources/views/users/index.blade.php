@extends('layouts.app')

@section('title', 'Users')

@section('header')
    <x-ui.page-header title="Users">
        Manage user accounts, their roles, status, and two-factor authentication.
    </x-ui.page-header>
@endsection

@section('content')
    @php
        $emptyForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
            'role_id' => $defaultRoleId,
            'is_active' => 1,
        ];
        $statusOptions = ['1' => 'Active', '0' => 'Inactive'];
    @endphp

    <div
        class="ui-card"
        x-data="dataTable(@js($users), {
            searchKeys: ['name', 'email'],
            sortKey: 'id',
            perPage: 10,
            emptyForm: @js($emptyForm),
            entityLabel: 'user',
            rowLabel: (row) => row.name,
            onSubmit: (form, mode) => fetch(mode === 'add' ? '{{ route('admin.users.store') }}' : `{{ url('/admin/users') }}/${form.id}`, {
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
                    throw new Error(data.message ?? Object.values(data.errors ?? {}).flat()[0] ?? 'Failed to save user.');
                }
                // Rows are rendered once by Blade at page load: a brand-new user has no
                // matching <tr>, and the role/status/2FA badges' colors are baked in from
                // server-rendered classes, not reactive bindings — reload once the toast
                // has had a moment to show so the table reflects the change.
                setTimeout(() => window.location.reload(), 600);
                return data;
            }),
            onDelete: (row) => fetch(`{{ url('/admin/users') }}/${row.id}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            }).then(async (response) => {
                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    throw new Error(data.message ?? 'Failed to delete user.');
                }
            }),
        })"
    >
        <div class="flex flex-col gap-4 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:border-gray-800">
            <x-ui.table.search x-model.debounce.300ms="search" placeholder="Search users..." />

            @can('users.create')
                <x-ui.button variant="primary" @click="openAddModal()">
                    <x-slot:iconLeft><x-icon.plus /></x-slot:iconLeft>
                    Add User
                </x-ui.button>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-sm">
                <thead class="bg-gray-50 dark:bg-white/[0.03]">
                    <tr>
                        <x-ui.table.sort-th sort-key="id">ID</x-ui.table.sort-th>
                        <x-ui.table.sort-th sort-key="name">User</x-ui.table.sort-th>
                        <x-ui.table.sort-th sort-key="role_name">Role</x-ui.table.sort-th>
                        <x-ui.table.sort-th sort-key="is_active">Status</x-ui.table.sort-th>
                        <x-ui.table.sort-th sort-key="two_factor_enabled">2FA</x-ui.table.sort-th>
                        <x-ui.table.sort-th sort-key="created_at">Registered</x-ui.table.sort-th>
                        <th scope="col" class="px-4 py-3.5 text-right text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Actions</th>
                    </tr>
                </thead>

                <tbody x-ref="tbody" class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($users as $user)
                        <tr
                            data-row-id="{{ $user['id'] }}"
                            x-data="{ menuOpen: false }"
                            x-show="visibleIds.includes({{ $user['id'] }})"
                            x-cloak
                            @table-actions-menu-toggle="menuOpen = $event.detail.open"
                            :class="menuOpen ? 'bg-gray-50 dark:bg-white/[0.02]' : ''"
                            class="transition hover:bg-gray-50 dark:hover:bg-white/[0.02]"
                        >
                            <td class="px-4 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">#{{ $user['id'] }}</td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user['name'] }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $user['email'] }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <x-ui.badge variant="{{ $user['role_variant'] }}" size="sm">{{ $user['role_name'] }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <x-ui.badge :variant="$user['is_active'] ? 'success' : 'secondary'" size="sm">{{ $user['is_active'] ? 'Active' : 'Inactive' }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <x-ui.badge :variant="$user['two_factor_enabled'] ? 'success' : 'secondary'" size="sm">{{ $user['two_factor_enabled'] ? 'Enabled' : 'Disabled' }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $user['registered_label'] }}</td>
                            <td class="px-4 py-4 text-right">
                                {{-- Plain element (not a Blade component tag) so @js() below is
                                     actually expanded by Blade — component-tag attributes are
                                     extracted as literal strings before directive compilation. --}}
                                <div x-data="{ user: @js($user) }" class="inline-flex">
                                    <x-ui.table.actions-menu>
                                        @can('users.view')
                                            <x-ui.table.actions-menu-item @click="openViewModal(user)">
                                                <x-slot:icon><x-icon.eye class="h-4 w-4" /></x-slot:icon>
                                                View
                                            </x-ui.table.actions-menu-item>
                                        @endcan
                                        @can('users.edit')
                                            <x-ui.table.actions-menu-item @click="openEditModal(user)">
                                                <x-slot:icon><x-icon.pencil class="h-4 w-4" /></x-slot:icon>
                                                Edit
                                            </x-ui.table.actions-menu-item>
                                        @endcan
                                        @can('users.impersonate')
                                            <template x-if="!user.is_admin && user.id !== {{ auth()->id() }}">
                                                <x-ui.table.actions-menu-item @click="$store.confirmDialog.open({
                                                    title: 'Impersonate this user?',
                                                    message: `You'll be signed in as '${user.name}' until you return to your own account.`,
                                                    variant: 'warning',
                                                    confirmText: 'Impersonate',
                                                    onConfirm: () => {
                                                        const form = document.createElement('form');
                                                        form.method = 'POST';
                                                        form.action = `{{ url('/admin/users') }}/${user.id}/impersonate`;
                                                        const token = document.createElement('input');
                                                        token.type = 'hidden';
                                                        token.name = '_token';
                                                        token.value = '{{ csrf_token() }}';
                                                        form.appendChild(token);
                                                        document.body.appendChild(form);
                                                        form.submit();
                                                    },
                                                })">
                                                    <x-slot:icon><x-icon.arrow-right class="h-4 w-4" /></x-slot:icon>
                                                    Impersonate
                                                </x-ui.table.actions-menu-item>
                                            </template>
                                        @endcan
                                        @can('users.disable_2fa')
                                            <template x-if="user.two_factor_enabled">
                                                <x-ui.table.actions-menu-item @click="$store.confirmDialog.open({
                                                    title: 'Disable two-factor authentication?',
                                                    message: `Disable two-factor authentication for &quot;${user.name}&quot;? They will need to set it up again.`,
                                                    variant: 'warning',
                                                    confirmText: 'Disable',
                                                    onConfirm: () => fetch(`{{ url('/admin/users') }}/${user.id}/disable-two-factor`, {
                                                        method: 'POST',
                                                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                    }).then(async (response) => {
                                                        if (!response.ok) {
                                                            const data = await response.json().catch(() => ({}));
                                                            $store.toast.show({ message: data.message ?? 'Failed to disable two-factor.', type: 'danger' });
                                                            return;
                                                        }
                                                        $store.toast.show({ message: `Two-factor authentication disabled for &quot;${user.name}&quot;.`, type: 'success' });
                                                        setTimeout(() => window.location.reload(), 600);
                                                    }),
                                                })">
                                                    <x-slot:icon><x-icon.lock-closed class="h-4 w-4" /></x-slot:icon>
                                                    Disable 2FA
                                                </x-ui.table.actions-menu-item>
                                            </template>
                                        @endcan
                                        @can('users.delete')
                                            @if($user['id'] !== auth()->id())
                                                <x-ui.table.actions-menu-item variant="danger" @click="confirmDelete(user)">
                                                    <x-slot:icon><x-icon.trash class="h-4 w-4" /></x-slot:icon>
                                                    Delete
                                                </x-ui.table.actions-menu-item>
                                            @endif
                                        @endcan
                                    </x-ui.table.actions-menu>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    <tr x-show="totalCount === 0">
                        <td colspan="7" class="px-4 py-6">
                            <x-ui.empty-state :bordered="false">
                                <x-slot:icon><x-icon.search class="h-6 w-6" /></x-slot:icon>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">No users found</p>
                                <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">No users match "<span x-text="search"></span>".</p>
                            </x-ui.empty-state>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 p-4 sm:p-6 dark:border-gray-800">
            <x-ui.table.pagination item="users" />
        </div>

        {{-- Add / Edit modal — one form, reused for both by toggling `formMode`. --}}
        <x-ui.modal show="showFormModal" :close-on-backdrop="false" max-width="lg" :title="null">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="formMode === 'add' ? 'Add User' : 'Edit User'"></h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="formMode === 'add' ? 'Fill in the details to create a new user.' : 'Update this user\'s details below.'"></p>

            <form x-ref="userForm" class="mt-4 space-y-5" @submit.prevent="submitForm()">
                <x-form.field label="Full Name" for="user_name" required>
                    <x-form.input id="user_name" placeholder="e.g. Jane Doe" x-model="form.name" required />
                </x-form.field>

                <x-form.field label="Email" for="user_email" required>
                    <x-form.input id="user_email" type="email" placeholder="e.g. jane@example.com" x-model="form.email" required />
                </x-form.field>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.field label="Password" for="user_password">
                        <x-form.password-input id="user_password" placeholder="Password" x-model="form.password" x-bind:required="formMode === 'add'" />
                    </x-form.field>

                    <x-form.field label="Confirm Password" for="user_password_confirmation">
                        <x-form.password-input id="user_password_confirmation" placeholder="Confirm password" x-model="form.password_confirmation" x-bind:required="formMode === 'add'" />
                    </x-form.field>
                </div>
                <p class="-mt-3 text-xs text-gray-500 dark:text-gray-400" x-show="formMode === 'edit'">Leave blank to keep the current password.</p>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.field label="Role" for="user_role" required>
                        <x-form.select id="user_role" :options="$roleOptions" :show-placeholder="false" x-model="form.role_id" required />
                    </x-form.field>

                    <x-form.field label="Status" for="user_status" required>
                        <x-form.select id="user_status" :options="$statusOptions" :show-placeholder="false" x-model="form.is_active" required />
                    </x-form.field>
                </div>
            </form>

            <x-slot:footer>
                <x-ui.button type="button" variant="secondary" style="outline" @click="showFormModal = false">Cancel</x-ui.button>
                <x-ui.button type="button" variant="primary" x-bind:disabled="submitting" @click="$refs.userForm.reportValidity() && submitForm()" x-text="submitting ? 'Saving…' : (formMode === 'add' ? 'Add User' : 'Update User')"></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        {{-- View modal — read-only snapshot of the selected row. --}}
        <x-ui.modal show="showViewModal" max-width="sm" :title="null">
            <template x-if="viewRow">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="viewRow.name"></h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500" x-text="viewRow.email"></p>

                    <x-ui.description-list class="mt-5">
                        <x-ui.description-list.item label="Role"><span x-text="viewRow.role_name"></span></x-ui.description-list.item>
                        <x-ui.description-list.item label="Status"><span x-text="viewRow.is_active ? 'Active' : 'Inactive'"></span></x-ui.description-list.item>
                        <x-ui.description-list.item label="Two-Factor"><span x-text="viewRow.two_factor_enabled ? 'Enabled' : 'Disabled'"></span></x-ui.description-list.item>
                        <x-ui.description-list.item label="Registered"><span x-text="viewRow.registered_label"></span></x-ui.description-list.item>
                    </x-ui.description-list>
                </div>
            </template>

            <x-slot:footer>
                <x-ui.button type="button" variant="secondary" style="outline" @click="showViewModal = false">Close</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
@endsection
