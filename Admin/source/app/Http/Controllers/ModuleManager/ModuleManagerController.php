<?php

namespace Modules\Admin\Http\Controllers\ModuleManager;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ModuleManagerController extends Controller
{
    /**
     * Display the module manager page.
     *
     * Placeholder for now — managing nwidart/laravel-modules modules from the
     * admin panel lands in a future pass.
     */
    public function index(): View
    {
        return view('admin::module-manager.index');
    }
}
