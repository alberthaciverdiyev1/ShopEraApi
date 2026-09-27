<?php

namespace Modules\Chat\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Chat\Http\Requests\AutoReplyRequest;
use Modules\Chat\Services\AutoReplyService;

class AutoReplyController extends Controller
{
    protected AutoReplyService $service;

    public function __construct(AutoReplyService $service)
    {
        $this->middleware('permission:full chat access')->only(['getAll', 'create', 'update', 'delete']);

        $this->service = $service;
    }

    public function getAll()
    {
        return $this->service->getAll();
    }

    public function create(AutoReplyRequest $request)
    {
        return $this->service->create($request);
    }

    public function update($id, AutoReplyRequest $request)
    {
        return $this->service->update($id, $request);
    }

    public function delete($id)
    {
        return $this->service->delete($id);
    }
}
