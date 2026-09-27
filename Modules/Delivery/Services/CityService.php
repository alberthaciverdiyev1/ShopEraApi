<?php

namespace Modules\Delivery\Services;

use App\Interfaces\ICrudInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Delivery\Http\Entities\City;

class CityService implements ICrudInterface
{
    private function generateKey(string $nameAz): string
    {
        $nameEn = Str::ascii($nameAz);

        return Str::studly($nameEn);
    }

    public function getAll($request): JsonResponse
    {
        $data = City::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn(City $city) => $this->resource($city));

        return responseHelper(__('Cities retrieved successfully.'), 200, $data);
    }

    public function add($request): JsonResponse
    {
        $nameAz = $request->input('name');
        $key = $this->generateKey($nameAz);
        $city = City::withTrashed()->where('key', $key)->first();

        if ($city && !$city->trashed() && $city->is_active) {
            return responseHelper(__('City already exists.'), 400);
        }

        if ($city) {
            $city->restore();
            $city->update(['name' => $nameAz, 'is_active' => true]);
        } else {
            $city = City::create(['key' => $key, 'name' => $nameAz, 'is_active' => true]);
        }

        return responseHelper(__('City added successfully.'), 201, $this->resource($city));
    }

    public function update(int|string $id, $request): JsonResponse
    {
        $newNameAz = $request->input('name');
        $city = City::query()->where('key', $id)->first();
        if (!$city) {
            return responseHelper("City not found.", 404);
        }

        $city->update(['name' => $newNameAz]);

        return responseHelper("City updated successfully.", 200, $this->resource($city));
    }

    public function delete(int|string $id): JsonResponse
    {
        $city = City::query()->where('key', $id)->first();
        if (!$city) {
            return responseHelper(__('City Not Found'), 404);
        }

        $city->update(['is_active' => false]);
        $city->delete();

        return responseHelper("City deleted successfully.", 200);
    }

    public function details(int|string $id): JsonResponse
    {
        $city = City::query()->where('key', $id)->first();
        if (!$city) {
            return responseHelper(__('City Not Found'), 404);
        }

        return responseHelper(__('City details retrieved.'), 200, $this->resource($city));
    }

    private function resource(City $city): array
    {
        return [
            'id' => $city->key,
            'key' => $city->key,
            'name' => $city->name,
        ];
    }
}
