<?php

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payment\Service\PaymentService;

class PaymentController extends Controller
{
    private PaymentService $service;

    function __construct(PaymentService $service)
    {
        $this->service = $service;
    }
    public function start(Request $request)
    {
       return $this->service->start($request);
    }

    public function createPayment(Request $request)
    {
        return $this->service->createPayment($request);
    }

    public function success(Request $request)
    {
        return $this->service->success($request);
    }

    public function error(Request $request)
    {
      return $this->service->error($request);
    }

    public function status(Request $request)
    {
        return $this->service->status($request);
    }

    public function result(Request $request)
    {
         return $this->service->result($request);
    }
}
