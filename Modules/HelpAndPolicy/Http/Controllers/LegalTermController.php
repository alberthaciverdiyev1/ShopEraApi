<?php

namespace Modules\HelpAndPolicy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpAndPolicy\Http\Requests\LegalTerm\LegalTermAddRequest;
use Modules\HelpAndPolicy\Http\Requests\LegalTerm\LegalTermUpdateRequest;
use Modules\HelpAndPolicy\Services\LegalTermsService;

class LegalTermController extends Controller
{
    private LegalTermsService $service;

    function __construct(LegalTermsService $service)
    {
        $this->middleware('permission:update legal-terms')->only('update');

        $this->service = $service;
    }

    public function getAll(Request $request)
    {
        return $this->service->getAll($request);
    }

    public function privacyAndPolicy()
    {
        return $this->service->privacyAndPolicy();
    }

    public function getAllAdmin(Request $request)
    {
        return $this->service->getAllAdmin($request);
    }


    public function update(LegalTermUpdateRequest $request,$type)
    {
        return $this->service->update($request,$type);
    }

}
