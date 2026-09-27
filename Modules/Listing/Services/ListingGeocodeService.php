<?php

namespace Modules\Listing\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as StatusCode;

/**
 * Address search for the map field.
 *
 * The app does not call the geocoder itself. OpenStreetMap's Nominatim asks
 * that a distributed app go through its publisher's own server so the traffic
 * can be identified, cached and capped - and going through us also means the
 * provider can be swapped without shipping a new app.
 */
class ListingGeocodeService
{
    private const ENDPOINT = 'https://nominatim.openstreetmap.org';

    /*
     * Nominatim yazılan sözü tam uyğunluqla axtarır: "Nizami küç" heç nə
     * qaytarmır, ona görə yazdıqca təklif vermir. Photon elə bunun üçün
     * qurulub — eyni OSM məlumatı, amma prefiks uyğunluğu ilə. Əvvəl o
     * soruşulur, boş qayıdarsa Nominatim-ə keçilir.
     */
    private const AUTOCOMPLETE = 'https://photon.komoot.io/api';

    /** Axtarış Azərbaycanla məhdudlaşır. */
    private const BBOX = '44.77,38.39,50.37,41.91';

    /** Nominatim wants a real contact address in the agent string. */
    private const AGENT = 'TeymurStore/1.0 (+https://teymurstore.az)';

    /** Places do not move, so an answer is worth keeping for a day. */
    private const TTL_SECONDS = 86400;

    public function search(Request $request)
    {
        $query = trim((string) $request->input('q', ''));

        if (mb_strlen($query) < 2) {
            return responseHelper('Operation successful.', StatusCode::HTTP_OK, ['items' => []]);
        }

        $places = Cache::remember(
            'listing-geocode:s2:'.md5(mb_strtolower($query)),
            self::TTL_SECONDS,
            function () use ($query) {
                $places = $this->suggest($query);

                return $places !== [] ? $places : array_map(
                    fn (array $row) => $this->place($row),
                    $this->ask('/search', [
                        'q' => $query,
                        'format' => 'jsonv2',
                        'limit' => 8,
                        // The ads are local, so the search is too: it keeps
                        // the list short and the names in the right language.
                        'countrycodes' => 'az',
                        'accept-language' => 'az',
                        'addressdetails' => 1,
                    ]),
                );
            },
        );

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, ['items' => $places]);
    }

    /** The name of a point the seller dropped by hand. */
    public function reverse(Request $request)
    {
        $latitude = $request->input('lat');
        $longitude = $request->input('lng');

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return responseHelper('lat and lng are required.', StatusCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Rounded to about 10 m: dragging a pin a few metres should not go
        // out to the geocoder again.
        $key = 'listing-geocode:r:'.round((float) $latitude, 4).','.round((float) $longitude, 4);

        $row = Cache::remember($key, self::TTL_SECONDS, fn () => $this->ask('/reverse', [
            'lat' => (float) $latitude,
            'lon' => (float) $longitude,
            'format' => 'jsonv2',
            'accept-language' => 'az',
            'zoom' => 18,
            'addressdetails' => 1,
        ]));

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'place' => $row === [] ? null : $this->place(is_array($row[0] ?? null) ? $row[0] : $row),
        ]);
    }

    /**
     * Yazdıqca gələn təkliflər.
     *
     * @return array<int, array<string, mixed>>
     */
    private function suggest(string $query): array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::AGENT, 'Accept' => 'application/json'])
                ->timeout(6)
                ->get(self::AUTOCOMPLETE, [
                    'q' => $query,
                    'limit' => 8,
                    'lang' => 'default',
                    'bbox' => self::BBOX,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $features = $response->json('features');

            if (! is_array($features)) {
                return [];
            }

            $places = [];

            foreach ($features as $feature) {
                $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
                $coordinates = $feature['geometry']['coordinates'] ?? null;

                if (! is_array($coordinates) || count($coordinates) < 2) {
                    continue;
                }

                // Photon Azərbaycandan kənarı da qaytara bilər; bbox yalnız
                // sıralamaya təsir edir.
                if (($properties['countrycode'] ?? 'AZ') !== 'AZ') {
                    continue;
                }

                $name = trim((string) ($properties['name'] ?? ''));
                $where = collect([
                    $properties['street'] ?? null,
                    $properties['district'] ?? null,
                    $properties['city'] ?? $properties['county'] ?? null,
                ])->filter()->unique()->reject(fn ($part) => $part === $name)->take(2)->implode(', ');

                $places[] = [
                    'label' => trim($name.($where !== '' ? ', '.$where : ''), ', '),
                    'full_label' => trim(collect([
                        $name,
                        $properties['street'] ?? null,
                        $properties['district'] ?? null,
                        $properties['city'] ?? $properties['county'] ?? null,
                        $properties['state'] ?? null,
                    ])->filter()->unique()->implode(', '), ', '),
                    'city' => (string) ($properties['city'] ?? $properties['county'] ?? ''),
                    'lat' => round((float) $coordinates[1], 7),
                    'lng' => round((float) $coordinates[0], 7),
                ];
            }

            return $places;
        } catch (\Throwable $exception) {
            Log::warning('Autocomplete unreachable: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    private function ask(string $path, array $query): array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::AGENT, 'Accept' => 'application/json'])
                ->timeout(8)
                ->get(self::ENDPOINT.$path, $query);

            if (! $response->successful()) {
                return [];
            }

            $body = $response->json();

            // /search answers with a list, /reverse with one object.
            return is_array($body) ? (array_is_list($body) ? $body : [$body]) : [];
        } catch (\Throwable $exception) {
            Log::warning('Geocoder unreachable: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function place(array $row): array
    {
        $address = is_array($row['address'] ?? null) ? $row['address'] : [];

        // Nominatim's display_name runs to the country and the post code; the
        // first three parts are what a person would say out loud.
        $short = collect(explode(',', (string) ($row['display_name'] ?? '')))
            ->map(fn ($part) => trim($part))
            ->filter()
            ->take(3)
            ->implode(', ');

        return [
            'label' => $short !== '' ? $short : (string) ($row['name'] ?? ''),
            'full_label' => (string) ($row['display_name'] ?? ''),
            'city' => (string) ($address['city'] ?? $address['town'] ?? $address['village'] ?? $address['county'] ?? ''),
            'lat' => isset($row['lat']) ? round((float) $row['lat'], 7) : null,
            'lng' => isset($row['lon']) ? round((float) $row['lon'], 7) : null,
        ];
    }
}
