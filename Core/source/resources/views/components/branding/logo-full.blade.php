<img
    src="{{ \Modules\Core\Support\BrandAsset::url('logo-full-light.png') }}"
    alt="{{ config('app.name') }}"
    {{ $attributes->merge(['class' => 'w-auto dark:hidden']) }}
>
<img
    src="{{ \Modules\Core\Support\BrandAsset::url('logo-full-dark.png') }}"
    alt="{{ config('app.name') }}"
    {{ $attributes->merge(['class' => 'hidden w-auto dark:block']) }}
>
