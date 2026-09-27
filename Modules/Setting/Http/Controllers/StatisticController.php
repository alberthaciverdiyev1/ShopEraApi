<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Setting\Services\StatisticService;

class StatisticController
{
    private StatisticService $service;

    function __construct(StatisticService $service)
    {
        $this->service = $service;
    }

    public function statistics(Request $request)
    {
        return $this->service->statistics($request);
    }
}
