<img
    src="{{ \Modules\Core\Support\BrandAsset::url('logo-light.png') }}"
    alt="{{ config('app.name') }}"
    {{ $attributes->merge(['class' => 'shrink-0 dark:hidden']) }}
>
<img
    src="{{ \Modules\Core\Support\BrandAsset::url('logo-dark.png') }}"
    alt="{{ config('app.name') }}"
    {{ $attributes->merge(['class' => 'hidden shrink-0 dark:block']) }}
>
