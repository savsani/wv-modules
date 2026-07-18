<?php

namespace Modules\Core\Support;

class BrandAsset
{
    /**
     * URL for a brand image: the host app's own public/images/brand/{$file}
     * if it's published one there, otherwise this module's bundled default.
     */
    public static function url(string $file): string
    {
        return file_exists(public_path("images/brand/{$file}"))
            ? asset("images/brand/{$file}")
            : route('core.brand-asset', $file);
    }
}
