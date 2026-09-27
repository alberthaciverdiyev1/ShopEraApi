<?php

namespace Modules\Brand\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Brand\Http\Requests\BrandAddRequest;
use Modules\Brand\Http\Requests\BrandUpdateRequest;
use Modules\Brand\Services\BrandService;
use Nwidart\Modules\Facades\Module;

class BrandController extends Controller
{

    private BrandService $service;

    public function __construct(BrandService $service)
    {
       //     $this->middleware('permission:view brands')->only('list');
            $this->middleware('permission:add brand')->only('add');
           // $this->middleware('permission:details brand')->only('details');
            $this->middleware('permission:update brand')->only('update');
            $this->middleware('permission:delete brand')->only('delete');

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
    public function add(BrandAddRequest $request)
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
    public function update(BrandUpdateRequest $request, int $id)
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
