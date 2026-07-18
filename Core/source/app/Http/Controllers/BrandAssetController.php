<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandAssetController extends Controller
{
    /**
     * Only these bundled defaults are servable — $file comes straight off
     * the URL, so this is an allow-list, not a path-traversal guard.
     *
     * @var array<int, string>
     */
    protected const ALLOWED = [
        'logo-light.png',
        'logo-dark.png',
        'logo-full-light.png',
        'logo-full-dark.png',
    ];

    public function show(string $file): BinaryFileResponse
    {
        abort_unless(in_array($file, self::ALLOWED, true), 404);

        return response()->file(module_path('Core', "resources/images/brand/{$file}"));
    }
}
