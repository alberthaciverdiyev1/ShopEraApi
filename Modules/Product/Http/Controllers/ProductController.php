<?php

namespace Modules\Product\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Http\Requests\ProductAddRequest;
use Modules\Product\Http\Requests\ProductUpdatePricesRequest;
use Modules\Product\Http\Requests\ProductUpdateRequest;
use Modules\Product\Services\ProductService;
use Nwidart\Modules\Facades\Module;

class ProductController extends Controller
{

    private ProductService $service;

    public function __construct(ProductService $service)
    {
      //  $this->middleware('permission:view products')->only('list');
        $this->middleware('permission:add product')->only('add');
       // $this->middleware('permission:details product')->only('details');
        $this->middleware('permission:update product')->only('update','updatePrices');
        $this->middleware('permission:delete product')->only('delete');
        $this->middleware('permission:statistics product')->only('statistics');
        $this->middleware('permission:details-admin product')->only('detailsAdmin');

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
    public function add(ProductAddRequest $request)
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

    public function storyVideos()
    {
        return $this->service->storyVideos();
    }

    public function storyVideosAdmin(Request $request)
    {
        return $this->service->storyVideosAdmin($request);
    }

    public function activateStoryVideo(int $id)
    {
        return $this->service->activateStoryVideo($id);
    }

    public function deactivateStoryVideo(int $id)
    {
        return $this->service->deactivateStoryVideo($id);
    }

    public function detailsAdmin(int $id)
    {
        return $this->service->detailsAdmin($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(ProductUpdateRequest $request, int $id)
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

    public function statistics()
    {
        return $this->service->statistics();
    }

    public function recommendedProductsList(Request $request)
    {
        $user = auth()->check() ? auth()->user() : null;

        return $this->service->recommendedProductsList($user, $request);
    }

    public function updatePrices(ProductUpdatePricesRequest $request)
    {
        return $this->service->updatePrices($request);
    }

}
