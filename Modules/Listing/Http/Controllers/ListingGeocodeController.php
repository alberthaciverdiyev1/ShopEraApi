<?php

namespace Modules\Listing\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Listing\Services\ListingGeocodeService;

/**
 * Address search for the map field. Open, like the rest of browsing: an ad is
 * placed after signing in, but the map behind the form is not a secret.
 */
class ListingGeocodeController extends Controller
{
    public function __construct(private readonly ListingGeocodeService $geocode)
    {
    }

    public function search(Request $request)
    {
        return $this->geocode->search($request);
    }

    public function reverse(Request $request)
    {
        return $this->geocode->reverse($request);
    }
}
