<?php

namespace Modules\Listing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Listing\Services\ListingService;

/**
 * The ads: everyone may browse them, only their owner may touch them.
 */
class ListingController extends Controller
{
    public function __construct(private readonly ListingService $listings)
    {
    }

    public function index(Request $request)
    {
        return $this->listings->index($request);
    }

    public function show(int $id, Request $request)
    {
        return $this->listings->show($id, $request);
    }

    public function similar(int $id, Request $request)
    {
        return $this->listings->similar($id, $request);
    }

    public function store(Request $request)
    {
        return $this->listings->store($request);
    }

    public function update(int $id, Request $request)
    {
        return $this->listings->update($id, $request);
    }

    public function destroy(int $id, Request $request)
    {
        return $this->listings->destroy($id, $request);
    }

    public function mine(Request $request)
    {
        return $this->listings->mine($request);
    }

    public function renew(int $id, Request $request)
    {
        return $this->listings->renew($id, $request);
    }

    public function archive(int $id, Request $request)
    {
        return $this->listings->archive($id, $request);
    }
}
