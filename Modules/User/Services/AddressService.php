<?php

namespace Modules\User\Services;

use App\Interfaces\ICrudInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Modules\Delivery\Entities\City;
use Modules\User\Entities\Address;
use Modules\User\Http\Resources\AddressResource;

class AddressService implements ICrudInterface
{
    private Address $model;

    public function __construct(Address $model)
    {
        $this->model = $model;
    }

    public function getAll($request): JsonResponse
    {
        $id = auth()->id();
        $data = $this->model->where('user_id', $id)->get();

        return responseHelper(__('Addresses retrieved successfully.'), 200, AddressResource::collection($data));

    }

    public function details(int $id, $without_user_check = false): JsonResponse
    {
        try {
            $address = $this->model;
            if (! $without_user_check) {
                $address->where('user_id', auth()->id());
            }

            $address->findOrFail($id);

            return responseHelper(__('Address retrieved successfully.'), 200, new AddressResource($address));

        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Address not found.'), 403, []);
        }
    }

    /**
     * Add address
     */
    //    public function add($request): JsonResponse
    //    {
    //        $validated = $request->validated();
    //        $validated['user_id'] = auth()->id();
    //
    //        $hasAddress = $this->model->where('user_id', $validated['user_id'])->exists();
    //
    //        $validated['is_default'] = !$hasAddress;
    //
    //
    //        \Log::error($validated);
    //        return handleTransaction(
    //            fn() => $this->model->create($validated)->refresh(),
    //            'Address added successfully.',
    //            AddressResource::class
    //        );
    //    }

    public function add($request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        $hasAddress = $this->model->where('user_id', $validated['user_id'])->exists();
        $validated['is_default'] = ! $hasAddress;

        if (! empty($validated['city'])) {
            $city = City::findMatching($validated['city']);
            if (! $city) {
                return responseHelper(__('Invalid city name.'), 403);
            }

            $validated['city'] = $city->key;
        }

        return handleTransaction(
            fn () => $this->model->create($validated)->refresh(),
            'Address added successfully.',
            AddressResource::class
        );
    }

    public function update(int $id, $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if (! empty($data['city'])) {
                $city = City::findMatching($data['city']);
                if (! $city) {
                    return responseHelper(__('Invalid city name.'), 403);
                }

                $data['city'] = $city->key;
            }

            $address = $this->model
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return handleTransaction(function () use ($address, $data) {
                if (! empty($data['is_default']) && $data['is_default']) {
                    $this->model
                        ->where('user_id', auth()->id())
                        ->where('id', '<>', $address->id)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                }

                return tap($address)->update($data)->refresh();
            }, 'Address updated successfully.', AddressResource::class);

        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Address not found.'), 403, []);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $address = $this->model
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return handleTransaction(
                fn () => tap($address)->delete(),
                'Address deleted successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Address not found.'), 403, []);
        }
    }
}
