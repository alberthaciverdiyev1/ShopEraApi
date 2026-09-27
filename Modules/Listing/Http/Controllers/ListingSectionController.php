<?php

namespace Modules\Listing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Listing\Http\Resources\ListingSectionResource;
use Modules\Listing\Services\ListingSectionService;
use Symfony\Component\HttpFoundation\Response as StatusCode;

/**
 * What the apps read: the sections on offer and, for one of them, the fields
 * its form and filter are built from.
 */
class ListingSectionController extends Controller
{
    public function __construct(private readonly ListingSectionService $sections)
    {
    }

    public function index()
    {
        return responseHelper('Operation successful.', StatusCode::HTTP_OK, $this->sections->published());
    }

    /**
     * Settings the apps need but cannot guess: where the map tiles come from.
     *
     * A separate call rather than part of a section, because it is the same
     * for every one of them and because changing the tile provider should not
     * mean a new app build.
     */
    public function config()
    {
        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'map' => [
                'tile_url' => config('listing.map.tile_url'),
                'attribution' => config('listing.map.attribution'),
                'max_zoom' => config('listing.map.max_zoom'),
                'default_lat' => config('listing.map.default_lat'),
                'default_lng' => config('listing.map.default_lng'),
            ],
        ]);
    }

    public function show(string $key)
    {
        $section = $this->sections->show($key);

        if (! $section) {
            return responseHelper('Section not found.', StatusCode::HTTP_NOT_FOUND);
        }

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, (new ListingSectionResource($section))->resolve());
    }

    /**
     * The cities an ad can be placed in, straight from the table listings
     * point at. The shop's own city list is an enum keyed differently, and an
     * ad needs the row id, so this is its own small endpoint rather than a
     * change to something the published apps already read.
     */
    public function cities()
    {
        $cities = DB::table('cities')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'key', 'name']);

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, $cities->map(fn ($city) => [
            'id' => (int) $city->id,
            'key' => $city->key,
            'name' => $city->name,
        ])->all());
    }
}
