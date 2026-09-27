<?php

namespace Modules\Banner\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Banner\Services\BannerService;
use Nwidart\Modules\Facades\Module;

class BannerController extends Controller
{

    private BannerService $bannerService;

    public function __construct(BannerService $bannerService)
    {
        //$this->middleware('permission:view banners')->only('getAll');
        $this->middleware('permission:add banner')->only('add');
        $this->middleware('permission:delete banner')->only('delete');

        $this->bannerService = $bannerService;
    }


    public function getAll(Request $request)
    {
        return $this->bannerService->getAll($request);
    }

    public function add(Request $request)
    {
        return $this->bannerService->add($request);
    }

    public function delete($id)
    {
        return $this->bannerService->delete($id);
    }
}
