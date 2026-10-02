<?php

namespace App\Http\Controllers\Admin;

use Modules\Setting\Services\StatisticService;

class DashboardController extends AdminController
{
    protected string $title = 'İdarə paneli';

    public function index()
    {
        return view('admin.pages.dashboard', array_merge(
            ['title' => $this->title],
            app(StatisticService::class)->dashboard()
        ));
    }
}
