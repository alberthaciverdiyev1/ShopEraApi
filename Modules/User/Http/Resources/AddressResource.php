<?php

namespace Modules\User\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Delivery\Entities\City;

class AddressResource extends JsonResource
{
    public function toArray(Request $request)
    {
        $data = parent::toArray($request);

        if (! is_array($data)) {
            return $data;
        }

        $cityKey = $data['city'] ?? null;

        if ($cityKey) {
            $city = City::withTrashed()->where('key', $cityKey)->first();

            if ($city) {
                $data['city_key'] = $city->key;
                $data['city'] = $city->name;
            }
        }

        return $data;
    }
}
