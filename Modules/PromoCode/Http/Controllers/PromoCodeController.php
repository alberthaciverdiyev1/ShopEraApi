<?php

namespace Modules\PromoCode\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PromoCode\Http\Requests\PromoCodeAddRequest;
use Modules\PromoCode\Http\Requests\PromoCodeUpdateRequest;
use Modules\PromoCode\Services\PromoCodeService;

class PromoCodeController extends Controller
{
    private PromoCodeService $service;

    public function __construct(PromoCodeService $service)
    {
        $this->middleware('permission:view promo-codes')->only('getAll');
        // Shoppers validate their own code at checkout, so these two are open
        // to any signed-in user; managing codes still needs the permissions.
        $this->middleware('permission:add promo-code')->only('add');
        $this->middleware('permission:details promo-code')->only('details');
        $this->middleware('permission:update promo-code')->only('update');
        $this->middleware('permission:delete promo-code')->only('delete');

        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function getAll(Request $request)
    {
        return $this->service->getAll($request);
    }

    public function check($code)
    {
        return $this->service->check($code);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function add(PromoCodeAddRequest $request)
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
    public function update(PromoCodeUpdateRequest $request, int $id)
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

    public function checkPromoCodeWithPrice(string $code, Request $request)
    {
        return $this->service->checkPromoCodeWithPrice($code, $request);
    }
    //    public function checkPromoCodeWithPrice(string $code, Request $request)
    //    {
    //        return $this->service->checkPromoCodeWithPrice($code, $request, true,);
    //    }
}
