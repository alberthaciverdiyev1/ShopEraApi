<?php

namespace Modules\Size\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Size\Http\Requests\SizeAddRequest;
use Modules\Size\Http\Requests\SizeUpdateRequest;
use Modules\Size\Services\SizeService;
use Nwidart\Modules\Facades\Module;

class SizeController extends Controller
{

    private SizeService $service;

    public function __construct(SizeService $service)
    {
        $this->middleware('permission:view sizes')->only('list');
        $this->middleware('permission:add size')->only('add');
        $this->middleware('permission:details size')->only('details');
        $this->middleware('permission:update size')->only('update');
        $this->middleware('permission:delete size')->only('delete');

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
    public function add(SizeAddRequest $request)
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
    public function update(SizeUpdateRequest $request, int $id)
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
