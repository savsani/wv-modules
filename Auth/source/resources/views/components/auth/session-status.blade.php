@props(['status', 'variant' => 'success'])

@if ($status)
    <x-ui.alert :variant="$variant" :icon="false" {{ $attributes }}>
        {{ $status }}
    </x-ui.alert>
@endif
