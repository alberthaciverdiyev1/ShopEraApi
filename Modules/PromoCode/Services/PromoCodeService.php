<?php

namespace Modules\PromoCode\Services;

use App\Interfaces\ICrudInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Delivery\Services\DeliveryService;
use Modules\PromoCode\Http\Entities\PromoCode;
use Modules\PromoCode\Http\Resources\PromoCodeResource;
use Modules\Product\Services\ProductPricingService;
use Modules\User\Http\Entities\Address;
use Modules\User\Http\Entities\Basket;
use Modules\User\Http\Entities\User;

class PromoCodeService
{

    private PromoCode $model;
    private DeliveryService $deliveryService;
    private ProductPricingService $pricingService;

    /**
     * @param PromoCode $model
     */
    function __construct(PromoCode $model, DeliveryService $deliveryService, ProductPricingService $pricingService)
    {
        $this->deliveryService = $deliveryService;
        $this->model = $model;
        $this->pricingService = $pricingService;
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function getAll($request): JsonResponse
    {
        $params = $request->all();
        $cacheKey = 'promo_code_list_' . md5(serialize($params));

        $data = Cache::remember($cacheKey, config('promo_code_list_cache_time'), function () use ($params) {
            $query = $this->model->query()->select(['id', 'code', 'discount_percent', 'is_active', 'user_count', 'created_at']);
            $query = filterLike($query, ['code', 'discount_percent'], $params);

            if (isset($params['is_active'])) {
                $query->where('is_active', $params['is_active']);
            } else {
                $query->where('is_active', 1);
            }

            return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();
        });

        return response()->json([
            'success' => 200,
            'message' => __('Promo Codes retrieved successfully.'),
            'data' => PromoCodeResource::collection($data),
        ]);
    }

    public function details(int $id, $inline_request = false)
    {
        try {
            $promoCode = $this->model->findOrFail($id);
            if ($inline_request) {
                return PromoCodeResource::make($promoCode);
            }
            return response()->json([
                'success' => 200,
                'message' => __('Promo Codes details retrieved successfully.'),
                'data' => PromoCodeResource::make($promoCode),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($inline_request) {
                return PromoCodeResource::make([]);
            }
            return response()->json([
                'success' => 403,
                'message' => __('Promo Code not found.'),
                'data' => [],
            ]);
        }
    }

//    public function check(string $code, $inline_request = false)
//    {
//        try {
//            $user = auth()->user();
//
//            $promoCode = $this->model
//                ->where('code', $code)
//                ->where('is_active', 1)
//                ->first();
//
//            if (!$promoCode) {
//                return responseHelper(__('Promo Code not found.'), 403, [], $inline_request);
//            }
//
//            if ($promoCode->user_count <= 0) {
//                return responseHelper(__('Promo Code usage limit reached.'), 403, [], $inline_request);
//            }
//
//            if ($user->usedPromoCodes()->where('promo_code_id', $promoCode->id)->whereNull('transaction_id')->exists()) {
//                return responseHelper(__('You have already used this Promo Code.'), 403, [], $inline_request);
//            }
//
//            $priceData = $this->checkPromoCodeWithPrice($promoCode, null,true);
//
//            $promoCode->setAttribute('discounted_price', $priceData["discounted_price"]);
//            $promoCode->setAttribute('original_price', $priceData["original_price"]);
//
//            return responseHelper(__('Promo Code checked successfully.'), 200, PromoCodeResource::make($promoCode), $inline_request);
//
//        } catch (\Exception $e) {
//            return responseHelper(__('An error occurred.'), 403, [], $inline_request);
//        }
//    }


    public function check(string $code, $inline_request = false, $request = null)
    {
        try {
            $user = auth()->user();

            $address_id = $request->address_id ?? null;

            $address = Address::where('user_id', $user->id)
                ->when($address_id, fn($q) => $q->where('id', $address_id),
                    fn($q) => $q->where('is_default', true))
                ->first();

            if (!$address) {
                return responseHelper(__('Please set a valid default address with a city before placing an order.'), 403);
            }

            $deliveryResponse = $this->deliveryService
                ->details(null, $address->city)
                ->getData(true);

            $delivery = $deliveryResponse['data'] ?? null;
            if (!$delivery) {
                return responseHelper(__('Delivery service is not available for your city.'), 403);
            }


            $promoCode = $this->model
                ->where('code', $code)
                ->where('is_active', 1)
                ->first();

            if (!$promoCode) {
                return responseHelper(__('Promo Code not found.'), 404, [], $inline_request);
            }

            if ($promoCode->user_count <= 0) {
                return responseHelper(__('Promo Code usage limit reached.'), 403, [], $inline_request);
            }

            $alreadyUsed = $user->usedPromoCodes()
                ->where('promo_code_id', $promoCode->id)
                ->whereNull('transaction_id')
                ->exists();

            if ($alreadyUsed) {
                return responseHelper(__('You have already used this Promo Code.'), 403, [], $inline_request);
            }

            $basket = Basket::with('product.sizes')
                ->where('user_id', $user->id)
                ->where('selected', true)
                ->where('is_ordered', false)
                ->get();

            if ($basket->isEmpty()) {
                return responseHelper(__('Your basket is empty.'), 403, [], $inline_request);
            }

            $totalPrice = $this->pricingService->basketTotal($basket, $user);


            $shipping_price = $totalPrice < $delivery['free_from'] ? ($delivery['price'] ?? 0) : 0;

            $discountedPrice = round($totalPrice * (1 - $promoCode->discount_percent / 100), 2);

            $promoCode->setAttribute('original_price', $totalPrice + $shipping_price);
            $promoCode->setAttribute('discounted_price', $discountedPrice + $shipping_price);

            return responseHelper(__('Promo Code checked successfully.'),
                200,
                PromoCodeResource::make($promoCode),
                $inline_request
            );

        } catch (\Exception $e) {
            \Log::error("Promo Code Check Error: " . $e->getMessage());
            return responseHelper(__('An error occurred during calculation.'), 500, [], $inline_request);
        }
    }

    public function checkPromoCodeWithPrice(string $code, $request, bool $inline_request = false)
    {
        try {
            $user = auth()->user();


            $address_id = $request->address_id ?? null;

            $address = Address::where('user_id', $user->id)
                ->when($address_id, fn($q) => $q->where('id', $address_id),
                    fn($q) => $q->where('is_default', true))
                ->first();

            if (!$address) {
                return responseHelper(__('Please set a valid default address with a city before placing an order.'), 403);
            }

            $deliveryResponse = $this->deliveryService
                ->details(null, $address->city)
                ->getData(true);

            $delivery = $deliveryResponse['data'] ?? null;
            if (!$delivery) {
                return responseHelper(__('Delivery service is not available for your city.'), 403);
            }


            $promoCode = $this->model
                ->where('code', $code)
                ->where('is_active', true)
                ->first();

            if (!$promoCode) {
                return responseHelper(__('Promo code not found.'), 403, []);
            }

            if ($promoCode->user_count <= 0) {
                return responseHelper(__('Promo code usage limit reached.'), 403, []);
            }

            if ($user->usedPromoCodes()->where('promo_code_id', $promoCode->id)->whereNull('transaction_id')->exists()) {
                return responseHelper(__('You have already used this promo code.'), 403, []);
            }

            $basket = Basket::with('product.sizes')
                ->where('user_id', $user->id)
                ->where('selected', true)
                ->where('is_ordered', false)
                ->get();

            if ($basket->isEmpty()) {
                return responseHelper(__('Your basket is empty.'), 403, []);
            }


            $totalPrice = $this->pricingService->basketTotal($basket, $user);

            $discountedPrice = round($totalPrice * (1 - $promoCode->discount_percent / 100), 2);

            $shipping_price = $totalPrice < $delivery['free_from'] ? ($delivery['price'] ?? 0) : 0;


            if ($inline_request) {
                return [
                    'original_price' => $totalPrice + $shipping_price,
                    //   'discount_percent' => $promoCode->discount_percent,
                    'discounted_price' => $discountedPrice + $shipping_price,
                ];
            }

            return responseHelper(__('Promo code checked successfully.'), 200, [
                'original_price' => $totalPrice + $shipping_price,
                //   'discount_percent' => $promoCode->discount_percent,
                'discounted_price' => $discountedPrice + $shipping_price,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Promo code check failed', [
                'code' => $code,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return responseHelper(__('An unexpected error occurred.'), 403, []);
        }
    }


    /**
     * Add promoCode
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        $promoCode = handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Promo Code added successfully.',
            PromoCodeResource::class
        );

        Cache::forget('promo_code_list_' . md5(serialize([])));

        return $promoCode;
    }


    /**
     * Update promoCode
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $promoCode = handleTransaction(
            function () use ($validated, $id) {
                $promoCode = $this->model->findOrFail($id);
                $promoCode->update($validated);
                return $promoCode->refresh();
            },
            'Promo Code updated successfully.',
            PromoCodeResource::class
        );

        Cache::forget('promo_code_list_*');

        return $promoCode;
    }

    /**
     * Delete promoCode
     */
    public function delete(int $id): JsonResponse
    {
        $response = handleTransaction(
            function () use ($id) {
                $promoCode = $this->model->findOrFail($id);
                $promoCode->delete();
                return $promoCode;
            },
            'Promo Code deleted successfully.'
        );

        Cache::forget('promo_code_list_*');

        return $response;
    }

    public function applyPromoCodeToUser(int $promoCodeId, int $userId, bool $inline_request = false, int $orderId = null, $transactionId = null): bool
    {
        try {
            $promoCode = $this->model->findOrFail($promoCodeId);

            if ($promoCode->user_count <= 0) {
                return false;
            }

            $user = User::findOrFail($userId);

            if (!$user->usedPromoCodes()->where('promo_code_id', $promoCodeId)->whereNull('transaction_id')->exists()) {

                $pivotData = [];
                if ($orderId !== null) {
                    $pivotData['order_id'] = $orderId;
                }
                if ($transactionId) {
                    $pivotData['transaction_id'] = $transactionId;
                }

                $user->usedPromoCodes()->attach($promoCodeId, $pivotData);

            } else {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            \Log::error("Failed to apply promo code {$promoCodeId} to user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    public function decrementPromoCodeCount($promoCodeId): void
    {
        $promoCode = $this->model->findOrFail($promoCodeId);

        if ($promoCode->user_count > 0) {
            $promoCode->decrement('user_count');
        }
    }

}
