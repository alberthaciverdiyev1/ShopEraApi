<?php

namespace Modules\Popup\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Popup\Http\Requests\PopupAddRequest;
use Modules\Popup\Services\PopupService;
use Nwidart\Modules\Facades\Module;

class PopupController extends Controller
{

    private PopupService $service;

    public function __construct(PopupService $service)
    {
        $this->service = $service;
        $this->middleware('permission:add popup')->only('add');
        $this->middleware('permission:active popup')->only('showHome');
        $this->middleware('permission:delete popup')->only('delete');
    }

    public function list(Request $request)
    {
        return $this->service->list($request);
    }

    public function showOne()
    {
        return $this->service->showOne();
    }

    public function add(PopupAddRequest $request)
    {
        return $this->service->add($request);
    }

    public function showHome(int $id)
    {
        return $this->service->showHome($id);
    }

    public function delete(int $id)
    {
        return $this->service->delete($id);
    }

}
