<x-sidebar.link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">
    <x-slot:icon>
        <x-icon.user-group class="h-5 w-5 shrink-0" />
    </x-slot:icon>
    Users
</x-sidebar.link>

<x-sidebar.link href="{{ route('admin.roles.index') }}" :active="request()->routeIs('admin.roles.*')">
    <x-slot:icon>
        <x-icon.identification class="h-5 w-5 shrink-0" />
    </x-slot:icon>
    Roles
</x-sidebar.link>

<x-sidebar.link href="{{ route('admin.permissions.index') }}" :active="request()->routeIs('admin.permissions.*')">
    <x-slot:icon>
        <x-icon.lock-closed class="h-5 w-5 shrink-0" />
    </x-slot:icon>
    Permissions
</x-sidebar.link>

<x-sidebar.link href="{{ route('admin.module-manager.index') }}" :active="request()->routeIs('admin.module-manager.*')">
    <x-slot:icon>
        <x-icon.module-manager class="h-5 w-5 shrink-0" />
    </x-slot:icon>
    Module Manager
</x-sidebar.link>
