<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Http\Requests\ReviewAddRequest;
use Modules\Product\Http\Requests\ReviewUpdateRequest;
use Modules\Product\Services\ReviewService;

class ReviewController extends Controller
{
    private ReviewService $service;

    public function __construct(ReviewService $service)
    {
        // $this->middleware('permission:view reviews')->only('list');
        $this->middleware('permission:add review')->only('add');
        $this->middleware('permission:delete review')->only('deleteByAdmin');

        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function list(int $product_id)
    {
        return $this->service->list($product_id);
    }

    public function featured()
    {
        return $this->service->featured();
    }

    public function listAdmin(Request $request)
    {
        return $this->service->listAdmin($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function add(ReviewAddRequest $request)
    {
        return $this->service->add($request);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(int $id)
    {
        return $this->service->delete($id);
    }

    public function deleteByAdmin(int $id)
    {
        return $this->service->deleteByAdmin($id);
    }

    public function changeStatus(ReviewUpdateRequest $request)
    {
        return $this->service->changeStatus(
            $request->review_id,
            $request->status
        );
    }
}
