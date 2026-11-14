<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(): View
    {
        $tab = request()->query('tab', 'settings');
        $allowedTabs = ['settings', 'logout'];
        $tab = in_array($tab, $allowedTabs, true) ? $tab : 'settings';

        return view('storefront.dashboard.index', [
            'tab' => $tab,
        ]);
    }
}
