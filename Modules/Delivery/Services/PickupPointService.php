<?php

namespace Modules\Delivery\Services;

use App\Helpers\TranslateHelper as Translate;
use App\Interfaces\ICrudInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Delivery\Http\Entities\PickupPoint;
use Modules\Delivery\Http\Resources\PickupPointResource;

class PickupPointService implements ICrudInterface
{
    private PickupPoint $model;

    /**
     * @param PickupPoint $model
     */
    function __construct(PickupPoint $model)
    {
        /** @var PickupPoint|Builder $model */
        $this->model = $model;
    }

    public function getAll($request): JsonResponse
    {
        $query = $this->model->newQuery();
        $isAdmin = filter_var($request->query('is_admin', false), FILTER_VALIDATE_BOOLEAN);

        if ($request->has('is_active')) {
            $isActive = filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        } elseif (!$isAdmin) {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        $pickupPoints = $request->has('page')
            ? $query->latest()->paginate(min(max((int) $request->input('per_page', $request->input('limit', 20)), 1), 100))
            : $query->latest()->get();

        return responseHelper(__('Pickup points retrieved successfully.'),
            200,
            PickupPointResource::collection($pickupPoints)
        );
    }
    public function details(int $id): JsonResponse
    {
        $pickupPoint = $this->model->newQuery()->where('is_active', true)->find($id);

        if (!$pickupPoint) {
            return responseHelper(__('Pickup point not found.'), 404);
        }

        return responseHelper(__('Pickup point details retrieved successfully.'),
            200,
            new PickupPointResource($pickupPoint)
        );
    }

    public function add($request): JsonResponse
    {
        $data = is_array($request) ? $request : $request->validated();

        return handleTransaction(function () use ($data) {
            $pickupPoint = $this->model->newQuery()
                ->withTrashed()
                ->where('name', $data['name'])
                ->first();

            if ($pickupPoint) {
                if ($pickupPoint->trashed()) {
                    $pickupPoint->restore();
                }
                $pickupPoint->update($data);
            } else {
                $languages = ['az', 'ru', 'en', 'tr'];
                $sourceText = $data['delivery_time']['az'] ?? '';
                $deliveryTimeTranslations = [];

                foreach ($languages as $lang) {
                    $text = $data['delivery_time'][$lang] ?? (
                    !empty($sourceText) ? Translate::translate($sourceText, $lang) : ''
                    );

                    $deliveryTimeTranslations[$lang] = Str::lower($text);
                }

                $data['delivery_time'] = $deliveryTimeTranslations;
                $pickupPoint = $this->model->newQuery()->create($data);
            }

            return new PickupPointResource($pickupPoint);
        }, 'Pickup point added successfully.', null, 201);
    }
    public function update(int $id, $request): JsonResponse
    {
        $pickupPoint = $this->model->newQuery()->find($id);

        if (!$pickupPoint) {
            return responseHelper(__('Pickup point not found.'), 404);
        }

        $data = is_array($request) ? $request : $request->validated();

        return handleTransaction(function () use ($pickupPoint, $data) {

            if (isset($data['delivery_time']) && is_array($data['delivery_time'])) {
                $languages = ['az', 'ru', 'en', 'tr'];
                $sourceText = $data['delivery_time']['az'] ?? '';

                foreach ($languages as $lang) {
                    if (empty($data['delivery_time'][$lang]) && !empty($sourceText)) {
                        $data['delivery_time'][$lang] = Translate::translate($sourceText, $lang);
                    }

                    if (!empty($data['delivery_time'][$lang])) {
                        $data['delivery_time'][$lang] = Str::lower($data['delivery_time'][$lang]);
                    }
                }
            }

            $pickupPoint->update($data);

            return new PickupPointResource($pickupPoint);
        }, 'Pickup point updated successfully.', null, 200);
    }

    public function detailsAdmin(Request $request, int $id): JsonResponse
    {
        $pickupPoint = $this->model->newQuery()->find($id);

        if (!$pickupPoint) {
            return responseHelper(__('Pickup point not found.'), 404);
        }

        $isAdmin = filter_var($request->query('is_admin'), FILTER_VALIDATE_BOOLEAN);

        if ($isAdmin) {
            $rawDeliveryTime = $pickupPoint->getRawOriginal('delivery_time');

            $deliveryTime = is_string($rawDeliveryTime)
                ? json_decode($rawDeliveryTime, true)
                : $rawDeliveryTime;

            $data = [
                'id'            => $pickupPoint->id,
                'starex_delivery_point_id' => $pickupPoint->starex_delivery_point_id,
                'name'          => $pickupPoint->name,
                'address'       => $pickupPoint->address,
                'price'         => $pickupPoint->price,
                'delivery_time' => $deliveryTime,
                'is_active'     => (bool)$pickupPoint->is_active,
                'created_at'    => $pickupPoint->created_at->format('Y-m-d H:i:s'),
            ];

            return responseHelper(__('Pickup point details retrieved successfully (Admin Mode).'), 200, $data);
        }

        return responseHelper(__('Pickup point details retrieved successfully.'),
            200,
            new PickupPointResource($pickupPoint)
        );
    }

    public function delete(int $id): JsonResponse
    {
        $pickupPoint = $this->model->newQuery()->find($id);

        if (!$pickupPoint) {
            return responseHelper(__('Pickup point not found.'), 404);
        }

        return handleTransaction(function () use ($pickupPoint) {

            $pickupPoint->delete();
            return null;

        }, 'Pickup point deleted successfully.', null, 204);
    }
}
