<?php

namespace Modules\Balance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Balance\Services\BalanceService;
use Modules\Balance\Http\Requests\BalanceRequest;

class BalanceController extends Controller
{
    private BalanceService $service;

    public function __construct(BalanceService $service)
    {
        $this->service = $service;

        $this->middleware('permission:view balances')->only('getBalance', 'getBalanceHistory');
        $this->middleware('permission:deposit balance')->only('deposit');
        $this->middleware('permission:withdraw balance')->only('withdraw');
        $this->middleware('permission:get-balance balance')->only('getBalance');
        $this->middleware('permission:history balance')->only('getBalanceHistory');
    }


    public function deposit(BalanceRequest $request)
    {
        return $this->service->deposit($request);
    }


    public function withdraw(BalanceRequest $request)
    {
        return $this->service->withdraw($request);
    }


    public function getBalance(Request $request)
    {
        $userId = $request->user_id ?? null;
        return $this->service->getBalance($userId);
    }

    public function getBalanceHistory(Request $request)
    {
        $userId = $request->user_id ?? null;
        $is_admin = $request->input('is_admin', false);

        return $this->service->getBalanceHistory($userId, $is_admin);
    }

    public function increaseBalance(Request $request)
    {
        return $this->service->increaseBalanceUrl($request);
    }

    public function success(Request $request)
    {
        return $this->service->increaseBalanceCallback($request);
    }

    public function error(Request $request)
    {
        return $this->service->error($request);
    }
}
