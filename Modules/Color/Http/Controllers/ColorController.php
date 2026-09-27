<?php

namespace Modules\Color\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Color\Http\Requests\ColorAddRequest;
use Modules\Color\Http\Requests\ColorUpdateRequest;
use Modules\Color\Services\ColorService;
use Nwidart\Modules\Facades\Module;

class ColorController extends Controller
{

    private ColorService $service;

    public function __construct(ColorService $service)
    {
        $this->middleware('permission:view colors')->only('list');
        $this->middleware('permission:add color')->only('add');
        $this->middleware('permission:details color')->only('details');
        $this->middleware('permission:update color')->only('update');
        $this->middleware('permission:delete color')->only('delete');

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
     * Store a newly created resource in storage.
     */
    public function add(ColorAddRequest $request)
    {
        return $this->service->add($request);
    }

    /**
     * Show the specified resource.
     */
    public function details(int $id)
    {
        return $this->service->details($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(ColorUpdateRequest $request, int $id)
    {
        return $this->service->update($request, $id);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function delete(int $id)
    {
        return $this->service->delete($id);
    }
}
