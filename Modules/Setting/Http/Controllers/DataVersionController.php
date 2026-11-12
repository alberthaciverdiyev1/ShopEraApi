<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Setting\Services\DataVersionService;

class DataVersionController extends Controller
{
    public function __construct(private readonly DataVersionService $service) {}

    public function index(): JsonResponse
    {
        return responseHelper('OK', 200, ['version' => $this->service->version()]);
    }
}
