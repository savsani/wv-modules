@props(['title'])

<div class="mb-6 text-center">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h1>
    {{ $slot }}
</div>
