<x-sidebar.link href="{{ route('admin.module-manager.index') }}" :active="request()->routeIs('admin.module-manager.*')">
    <x-slot:icon>
        <x-icon.module-manager class="h-5 w-5 shrink-0" />
    </x-slot:icon>
    Module Manager
</x-sidebar.link>
