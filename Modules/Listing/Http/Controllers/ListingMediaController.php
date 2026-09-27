<?php

namespace Modules\Listing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Listing\Services\ListingMediaService;

/**
 * Photos and the video, always on the seller's own ad.
 */
class ListingMediaController extends Controller
{
    public function __construct(private readonly ListingMediaService $media)
    {
    }

    public function store(int $id, Request $request)
    {
        return $this->media->upload($id, $request);
    }

    public function sort(int $id, Request $request)
    {
        return $this->media->sort($id, $request);
    }

    public function destroy(int $mediaId, Request $request)
    {
        return $this->media->destroy($mediaId, $request);
    }
}
