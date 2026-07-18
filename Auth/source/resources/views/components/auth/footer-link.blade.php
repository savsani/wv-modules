@props(['question', 'href'])

<p {{ $attributes->merge(['class' => 'mt-6 text-center text-sm text-gray-500 dark:text-gray-400']) }}>
    {{ $question }}
    <x-auth.text-link :href="$href">{{ $slot }}</x-auth.text-link>
</p>
