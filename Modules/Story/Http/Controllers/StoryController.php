<?php

namespace Modules\Story\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Story\Services\StoryService;

class StoryController extends Controller
{
    public function __construct(private readonly StoryService $service) {}

    public function list()
    {
        return $this->service->active();
    }
}
