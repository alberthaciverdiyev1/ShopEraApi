<?php

namespace Modules\Setting\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Setting\Http\SettingRequest;
use Modules\Setting\Services\SettingService;
use Nwidart\Modules\Facades\Module;

class SettingController extends Controller
{

    private SettingService $service;

    public function __construct(SettingService $service)
    {
      //  $this->middleware('permission:view setting')->only('list');
        $this->middleware('permission:update setting')->only('update');

        $this->service = $service;
    }


    /**
     * Display a listing of the resource.
     */
    public function list(Request $request)
    {
        return $this->service->list($request);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(SettingRequest $request)
    {
        return $this->service->update($request);
    }

    public function changeLocale(Request $request)
    {
        return $this->service->changeLocale($request);
    }

}
