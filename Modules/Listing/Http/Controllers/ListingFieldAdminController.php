<?php

namespace Modules\Listing\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Listing\Services\ListingFieldService;

class ListingFieldAdminController extends Controller
{
    public function __construct(private readonly ListingFieldService $fields)
    {
        $this->middleware('permission:manage listings');
    }

    public function store(int $id, Request $request)
    {
        return $this->fields->store($id, $request);
    }

    public function update(int $id, Request $request)
    {
        return $this->fields->update($id, $request);
    }

    public function destroy(int $id)
    {
        return $this->fields->destroy($id);
    }

    public function saveOptions(int $id, Request $request)
    {
        return $this->fields->saveOptions($id, $request);
    }

    public function destroyOption(int $id)
    {
        return $this->fields->destroyOption($id);
    }
}
