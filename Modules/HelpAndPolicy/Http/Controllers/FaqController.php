<?php

namespace Modules\HelpAndPolicy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpAndPolicy\Http\Requests\Faq\FaqAddRequest;
use Modules\HelpAndPolicy\Http\Requests\Faq\FaqUpdateRequest;
use Modules\HelpAndPolicy\Services\FaqService;

class FaqController extends Controller
{
    private FaqService $service;

    function __construct(FaqService $service)
    {
        $this->middleware('permission:add faq')->only('add');
        $this->middleware('permission:update faq')->only('update');
        $this->middleware('permission:delete faq')->only('delete');

        $this->service = $service;
    }

    public function getAll(Request $request)
    {
        return $this->service->getAll($request);
    }
    public function getAllAdmin(Request $request)
    {
        return $this->service->getAllAdmin($request);
    }

    public function add(FaqAddRequest $request)
    {
        return $this->service->add($request);
    }

    public function update(FaqUpdateRequest $request, int $id)
    {
        return $this->service->update($request, $id);
    }

    public function delete($id)
    {
        return $this->service->delete($id);
    }
}
