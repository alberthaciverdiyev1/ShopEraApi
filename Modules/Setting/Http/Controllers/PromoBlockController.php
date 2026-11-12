<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Setting\Services\PromoBlockService;

class PromoBlockController extends Controller
{
    public function __construct(private readonly PromoBlockService $service) {}

    public function index(): JsonResponse
    {
        return responseHelper('OK', 200, $this->service->all());
    }
}
