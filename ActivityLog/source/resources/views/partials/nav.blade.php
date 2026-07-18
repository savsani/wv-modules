<x-sidebar.link href="{{ route('admin.activity-log.index') }}" :active="request()->routeIs('admin.activity-log.*')">
    <x-slot:icon>
        <x-icon.clock class="h-5 w-5 shrink-0" />
    </x-slot:icon>
    Activity Log
</x-sidebar.link>
