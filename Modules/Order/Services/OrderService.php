<?php

namespace Modules\Order\Services;

use App\Enums\BalanceType;
use App\Enums\OrderStatus as OrderStatusEnum;
use App\Services\Notification\OrderStatusNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Balance\Entities\Balance;
use Modules\Balance\Services\BalanceService;
use Modules\Delivery\Entities\City;
use Modules\Delivery\Entities\PickupPoint;
use Modules\Delivery\Services\DeliveryInfoService;
use Modules\Delivery\Services\DeliveryService;
use Modules\Delivery\Services\PickupPointService;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderItem;
use Modules\Order\Entities\OrderStatus;
use Modules\Order\Http\Resources\OrderDetailResource;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Payment\Service\EPointService;
use Modules\Product\Entities\Product;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Services\ProductPricingService;
use Modules\Product\Services\ProductService;
use Modules\PromoCode\Entities\PromoCode;
use Modules\PromoCode\Services\PromoCodeService;
use Modules\Setting\Services\SettingService;
use Modules\User\Entities\Address;
use Modules\User\Entities\Basket;
use Modules\User\Services\AddressService;

class OrderService
{
    private Order $model;

    private DeliveryService $deliveryService;

    private BalanceService $balanceService;

    private PromoCodeService $promoCodeService;

    private EPointService $paymentService;

    private ProductService $productService;

    private PickupPointService $pickupPointService;

    private AddressService $addressService;

    private DeliveryInfoService $deliveryInfoService;

    private SettingService $settingsService;

    private ProductPricingService $pricingService;

    public function __construct(Order $model,
        DeliveryService $deliveryService,
        BalanceService $balanceService,
        PromoCodeService $promoCodeService,
        EPointService $paymentService,
        ProductService $productService,
        PickupPointService $pickupPointService,
        AddressService $addressService,
        DeliveryInfoService $deliveryInfoService,
        SettingService $settingsService,
        ProductPricingService $pricingService
    ) {
        $this->model = $model;
        $this->deliveryService = $deliveryService;
        $this->balanceService = $balanceService;
        $this->promoCodeService = $promoCodeService;
        $this->paymentService = $paymentService;
        $this->productService = $productService;
        $this->pickupPointService = $pickupPointService;
        $this->addressService = $addressService;
        $this->deliveryInfoService = $deliveryInfoService;
        $this->settingsService = $settingsService;
        $this->pricingService = $pricingService;
    }

    public function getAll(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $status = $request->query('status');

        $ordersQuery = $this->model
            ->with([
                'items' => function ($q) {
                    $q->with([
                        'product' => function ($query) {
                            $query->with([
                                'brand',
                                'category',
                                'images',
                                'reviews' => fn ($q) => $q->select('id', 'product_id', 'user_id', 'rate', 'comment', 'created_at'),
                            ])
                                ->withAvg('reviews', 'rate')
                                ->withCount('reviews');
                        },
                        'color',
                        'size',
                    ]);
                },
                'latestStatus',
                'address',
                'user',
            ])
            ->where('user_id', $userId);

        if ($status) {
            try {
                $statusEnum = OrderStatusEnum::fromString($status);
                $ordersQuery->whereHas('latestStatus', fn ($q) => $q->where('status', $statusEnum->value));
            } catch (\InvalidArgumentException $e) {
                return responseHelper(__('Invalid status parameter.'), 400);
            }
        }

        $orders = $ordersQuery
            ->orderBy('id', 'desc')
            ->get()
            ->unique('id')
            ->values();

        $this->attachDeliveryAddressFallback($orders);

        $orders->each(function ($order) {
            $order->items->transform(function ($item) {
                $product = $item->product;
                if ($product) {
                    $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
                    $product->rate_count = $product->reviews_count;
                    $product->is_favorite = Auth::check()
                        ? $product->favoritedBy()->where('user_id', Auth::id())->exists()
                        : false;
                }

                return $item;
            });
        });

        return responseHelper(
            __('Order data retrieved successfully.'),
            200,
            OrderResource::collection($orders)
        );
    }

    public function completedOrders(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $orders = $this->model
            ->with([
                'items' => function ($q) {
                    $q->with([
                        'product' => function ($query) {
                            $query->with([
                                'brand',
                                'category',
                                'images',
                                'colors',
                                'sizes',
                                'reviews' => fn ($q) => $q->select('id', 'product_id', 'user_id', 'rate', 'comment', 'created_at'),
                            ])
                                ->withAvg('reviews', 'rate')
                                ->withCount('reviews');
                        },
                        'color',
                        'size',
                    ]);
                },
                'latestStatus',
                'address',
                'user',
            ])
            ->where('user_id', $userId)
            ->whereHas('latestStatus', fn ($q) => $q->where('status', OrderStatusEnum::DELIVERED->value))
            ->latest()
            ->get();

        $orders->each(function ($order) {
            $order->items->transform(function ($item) {
                $product = $item->product;
                if ($product) {
                    $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
                    $product->rate_count = $product->reviews_count;
                    $product->is_favorite = Auth::check()
                        ? $product->favoritedBy()->where('user_id', Auth::id())->exists()
                        : false;
                }

                return $item;
            });
        });

        $products = $orders
            ->flatMap(fn ($order) => $order->items->pluck('product'))
            ->filter()
            ->values();

        return responseHelper(
            __('Delivered products retrieved successfully.'),
            200,
            ProductResource::collection($products)
        );
    }

    public function getAllAdmin($request): JsonResponse
    {
        $ordersQuery = $this->model
            ->with(['items', 'latestStatus', 'address', 'user'])
            ->latest();

        if ($request->has('status_key')) {
            $status = $request->query('status_key');

            try {
                $ordersQuery->whereHas('latestStatus', fn (Builder $q) => $q->where('status', $status)
                );
            } catch (\InvalidArgumentException $e) {
                return responseHelper(__('Invalid status parameter.'), 400);
            }
        }

        if ($search = $request->query('search')) {
            $search = Str::lower(trim($search));
            $searchDigits = preg_replace('/\D+/', '', $search);

            $cityKeys = City::query()
                ->whereRaw('LOWER(key) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                ->pluck('key');

            $ordersQuery->where(function (Builder $q) use ($search, $searchDigits, $cityKeys) {
                $q->whereHas('user', function (Builder $userQ) use ($search, $searchDigits) {
                    $userQ->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$search}%"]);

                    if ($searchDigits !== '') {
                        $userQ->orWhereRaw("regexp_replace(phone, '[^0-9]', '', 'g') LIKE ?", ["%{$searchDigits}%"]);
                    }
                });

                if ($cityKeys->isNotEmpty()) {
                    $q->orWhereHas('address', fn (Builder $addrQ) => $addrQ->whereIn('city', $cityKeys)
                    );
                }
            });
        }

        $perPage = (int) $request->query('limit', $request->query('per_page', 20));
        $perPage = max(1, min($perPage, 100));

        $orders = $ordersQuery->paginate($perPage);
        $resource = OrderResource::collection($orders)->response()->getData(true);

        return responseHelper(
            __('Order data retrieved successfully.'),
            200,
            $resource
        );
    }

    /**
     * Admin order list query with status + free-text (id / customer) filters.
     */
    public function adminQuery(Request $request): Builder
    {
        $query = $this->model->query()->with(['user', 'latestStatus', 'items'])->latest('id');

        if ($request->filled('status') && is_numeric($request->query('status'))) {
            $status = (int) $request->query('status');
            $query->whereHas('latestStatus', fn ($q) => $q->where('status', $status));
        }

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($inner) use ($term) {
                if (is_numeric($term)) {
                    $inner->orWhere('id', (int) $term);
                }

                $inner->orWhereHas('user', function ($user) use ($term) {
                    $user->where('name', 'like', "%{$term}%")
                        ->orWhere('surname', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            });
        }

        return $query;
    }

    /**
     * Order shown on the admin detail page, with everything the view needs.
     */
    public function adminFind(int $id): Order
    {
        return $this->model->query()
            ->with([
                'user',
                'address',
                'latestStatus',
                'statuses' => fn ($q) => $q->orderByDesc('id'),
                'items.product.images',
                'items.product.category',
            ])
            ->findOrFail($id);
    }

    public function adminDelete(int $id): void
    {
        $order = $this->model->query()->with('items')->findOrFail($id);

        $order->items()->delete();
        $order->delete();
    }

    public function details(int $id): JsonResponse
    {
        try {
            $order = $this->model
                ->with([
                    'items' => function ($q) {
                        $q->with([
                            'product' => function ($query) {
                                $query->with([
                                    'brand',
                                    'category',
                                    'images',
                                    'reviews' => function ($q) {
                                        $q->select('id', 'product_id', 'user_id', 'rate', 'comment', 'created_at');
                                    },
                                ])
                                    ->withAvg('reviews', 'rate')
                                    ->withCount('reviews');
                            },
                            'color',
                            'size',
                        ]);
                    },
                    'statuses',
                    'latestStatus',
                    'address',
                    'user',
                ])
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            $this->attachDeliveryAddressFallback($order);

            $order->items->transform(function ($item) {
                $product = $item->product;
                if ($product) {
                    $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
                    $product->rate_count = $product->reviews_count;

                    $product->is_favorite = Auth::check()
                        ? $product->favoritedBy()->where('user_id', Auth::id())->exists()
                        : false;
                }

                return $item;
            });

            return responseHelper(__('Order details retrieved successfully.'), 200, OrderDetailResource::make($order));

        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Order not found.'), 404);
        }
    }

    public function detailsAdmin(string $id): JsonResponse
    {
        try {
            $order = $this->model
                ->with([
                    'items' => function ($q) {
                        $q->with([
                            'product' => function ($query) {
                                $query->with([
                                    'brand',
                                    'category',
                                    'images',
                                    'reviews' => function ($q) {
                                        $q->select('id', 'product_id', 'user_id', 'rate', 'comment', 'created_at');
                                    },
                                ])
                                    ->withAvg('reviews', 'rate')
                                    ->withCount('reviews');
                            },
                            'color',
                            'size',
                        ]);
                    },
                    'statuses',
                    'address',
                    'latestStatus',
                    'user',
                ])
                ->when(
                    ctype_digit($id),
                    fn (Builder $query) => $query->whereKey((int) $id),
                    fn (Builder $query) => $query->where('transaction_id', $id)
                )
                ->firstOrFail();

            $this->attachDeliveryAddressFallback($order);

            $order->items->transform(function ($item) {
                $product = $item->product;
                if ($product) {
                    $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
                    $product->rate_count = $product->reviews_count;

                    $product->is_favorite = Auth::check()
                        ? $product->favoritedBy()->where('user_id', Auth::id())->exists()
                        : false;
                }

                return $item;
            });

            return responseHelper(__('Order details retrieved successfully.'), 200, OrderDetailResource::make($order));

        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Order not found.'), 404);
        }
    }

    public function deliveryAddress($type, $id = null)
    {
        switch ($type) {
            case 'STANDARD_FAST':
            case 'STANDARD':
                //                $address = $this->addressService->details($id, true);
                return Address::where('id', $id)->first();
                //                if ($address) {
                //                    $city = $address->city;
                //                    $res = $this->deliveryService->details(null, $city);
                //                    if (is_object($res) && method_exists($res, 'status') && $res->status() === 200) {
                //                        return $res->getData(true)['data'];
                //                    } else {
                //                        \Log::error('DeliveryService Standard Error:', [(array)$res]);
                //                    }
                //                }
                break;

            case 'PICKUP_POINT':
                $pickupPoint = PickupPoint::withTrashed()->find((int) $id);

                if ($pickupPoint) {
                    return [
                        'id' => (int) $pickupPoint->id,
                        'city' => (string) $pickupPoint->name,
                        'town_village_district' => '',
                        'street_building_number' => (string) $pickupPoint->address,
                        'unit_floor_apartment' => '',
                    ];
                }
                break;
            case 'TAKE_FROM_STORE':
                return [
                    'id' => 0,
                    'city' => __('Take From Store'),
                    'town_village_district' => '',
                    'street_building_number' => __('Snaker'),
                    'unit_floor_apartment' => '',
                ];
                break;

            default:
                break;
        }
    }

    private function attachDeliveryAddressFallback($orders): void
    {
        $collection = $orders instanceof Collection ? $orders : collect([$orders]);

        $collection->each(function ($order) {
            if (! $order || $order->address_id || ! $order->address_type) {
                return;
            }

            $address = $this->deliveryAddress($order->address_type, $order->address_type_id);
            if ($address instanceof Address) {
                $order->setRelation('address', $address);
            } elseif (is_array($address)) {
                $order->setRelation('address', $this->normalizeFallbackAddress($order, $address));
            }
        });
    }

    private function normalizeFallbackAddress(Order $order, array $address): array
    {
        return array_merge([
            'id' => 0,
            'user_id' => (int) $order->user_id,
            'city' => '',
            'town_village_district' => '',
            'street_building_number' => '',
            'unit_floor_apartment' => '',
            'is_default' => false,
            'full_name' => (string) ($order->user?->name ?? ''),
            'contact_number' => (string) ($order->user?->phone ?? ''),
            'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $order->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $address);
    }

    private function findReusablePendingPaymentOrder(int $userId, $basket, ?array $pricingSummary = null): ?Order
    {
        $transactionIds = $basket
            ->pluck('transaction_id')
            ->filter()
            ->unique()
            ->values();

        if ($transactionIds->count() !== 1) {
            return null;
        }

        $order = Order::with(['latestStatus', 'items'])
            ->where('user_id', $userId)
            ->where('transaction_id', $transactionIds->first())
            ->whereNull('paid_at')
            ->latest('id')
            ->first();

        if (! $order || $order->latestStatus?->status !== OrderStatusEnum::WAITING_PAYMENT) {
            return null;
        }

        if (! $this->basketMatchesOrderItems($basket, $order->items)) {
            return null;
        }

        if ($pricingSummary !== null) {
            $itemsTotal = round((float) $order->items->sum(fn ($item) => (float) $item->total_price), 2);

            if ($order->pricing_type && $order->pricing_type !== $pricingSummary['pricing_type']) {
                return null;
            }

            if (abs($itemsTotal - (float) $pricingSummary['final_total']) > 0.01) {
                return null;
            }
        }

        return $order;
    }

    private function basketMatchesOrderItems($basket, $orderItems): bool
    {
        $normalize = function ($items) {
            return $items
                ->map(fn ($item) => [
                    'product_id' => (int) $item->product_id,
                    'color_id' => $item->color_id ? (int) $item->color_id : null,
                    'size_id' => $item->size_id ? (int) $item->size_id : null,
                    'quantity' => (int) $item->quantity,
                ])
                ->sortBy(fn ($item) => implode(':', [
                    $item['product_id'],
                    $item['color_id'] ?? 0,
                    $item['size_id'] ?? 0,
                    $item['quantity'],
                ]))
                ->values()
                ->toArray();
        };

        return $normalize($basket) === $normalize($orderItems);
    }

    private function paymentRedirectResponse(string $transactionId): JsonResponse
    {
        return responseHelper(__('Order redirected to payment page.'), 200, [
            'payment_url' => route('api.payment.start', [
                'order_id' => $transactionId,
                'lang' => app()->getLocale(),
            ]),
            'pay_with_balance' => false,
            'transaction_id' => $transactionId,
        ]);
    }

    public function orderFromBasket($request): JsonResponse
    {
        $validated = $request->validated();

        $deliveryRes = self::calculateDeliveryPrice($validated['address_type'], $validated['address_type_id']);
        if ($deliveryRes->status() !== 200) {
            return $deliveryRes;
        }

        $deliveryData = $deliveryRes->getData(true);
        $delivery = $deliveryData['data'] ?? $deliveryData;

        $user = auth()->user();
        $appliedPromoId = null;
        $address_id = $request->address_id ?? null;
        $pay_with_balance = $request->pay_with_balance ?? false;
        $requestedPaymentType = strtoupper((string) $request->input('payment_type', 'CARD'));
        if ($pay_with_balance) {
            $requestedPaymentType = 'BALANCE';
        }
        $requiresCardPayment = $requestedPaymentType === 'CARD';
        $validated['paid_at'] = null;
        $validated['payment_type'] = $requestedPaymentType;

        if ($address_id) {
            unset($validated['address_id']);
        }
        unset($validated['pay_with_balance']);

        $lock = Cache::lock("order_from_basket_user_{$user->id}", 15);

        if (! $lock->get()) {
            return responseHelper(__('An order is already being prepared. Please try again in a few seconds.'), 429);
        }

        try {

            $isCourier = in_array($validated['address_type'] ?? 'STANDARD', ['STANDARD', 'STANDARD_FAST'], true);
            $address = null;

            if ($isCourier) {
                $targetAddressId = $validated['address_type_id'] ?? $address_id;
                $address = Address::where('user_id', $user->id)
                    ->when($targetAddressId, fn ($q) => $q->where('id', $targetAddressId),
                        fn ($q) => $q->where('is_default', true))
                    ->first();

                if (! $address) {
                    return responseHelper(__('Please set a valid default address with a city before placing an order.'), 403);
                }
            }

            $basket = Basket::with(['product.sizes'])
                ->where('user_id', $user->id)
                ->where('selected', true)
                ->where('is_ordered', false)
                ->get();

            if ($basket->isEmpty()) {
                return responseHelper(__('Your basket is empty.'), 403);
            }

            foreach ($basket as $item) {
                $product = $item->product;
                if (! $product) {
                    return responseHelper(__('Product not found.'), 403);
                }

                if (! Product::publiclyAvailable()->whereKey($product->id)->exists()) {
                    return responseHelper(__('One of the products in your basket is no longer available.'), 403);
                }

                $title = is_array($product->title)
                    ? ($product->title['az'] ?? reset($product->title))
                    : $product->title;

                if ($item->color_id && ! $product->colors()->where('color_id', $item->color_id)->exists()) {
                    return responseHelper("Selected color is not available for product: {$title}", 403);
                }

                if ($item->size_id && ! $product->sizes()->where('size_id', $item->size_id)->exists()) {
                    return responseHelper("Selected size is not available for product: {$title}", 403);
                }

                if ((int) $product->stock_count < (int) $item->quantity) {
                    $stockCount = Product::where('id', $product->id)->value('stock_count');
                    if ($stockCount < (int) $item->quantity) {
                        return responseHelper("Insufficient stock for product: {$title}", 403);
                    }
                }
            }

            $pricingSummary = $this->pricingService->basketPricingSummary(
                $basket,
                $this->settingsService->getWholesaleMinimalPurchasePrice()
            );

            if ($requiresCardPayment && $reusableOrder = $this->findReusablePendingPaymentOrder($user->id, $basket, $pricingSummary)) {
                return $this->paymentRedirectResponse($reusableOrder->transaction_id);
            }

            //            $validated['address_id'] = $address->id;
            $validated['user_id'] = $user->id;
            $validated['transaction_id'] = (string) strtoupper(Str::uuid());
            $validated['pricing_type'] = $pricingSummary['pricing_type'];

            $validated['total_price'] = $pricingSummary['final_total'];

            $minimal_purchase_price = $this->settingsService->getMinimalPurchasePrice();
            if ($validated['total_price'] < $minimal_purchase_price) {
                return responseHelper(
                    __('The total order amount must be at least :minimal_purchase_price AZN.', [
                        'minimal_purchase_price' => $minimal_purchase_price,
                    ]),
                    403
                );
            }

            $validated['discount_price'] = $pricingSummary['discount_amount'];

            if (isset($delivery['free_from']) && $delivery['free_from'] > 0 && $validated['address_type'] !== 'STANDARD_FAST') {
                $validated['shipping_price'] = $validated['total_price'] < $delivery['free_from']
                    ? ($delivery['total_delivery_price'] ?? 0) : 0;

            } elseif ($validated['address_type'] === 'STANDARD_FAST') {
                $validated['shipping_price'] = $delivery['total_delivery_price'];
            } else {
                $validated['shipping_price'] = $validated['total_price'] < 30 ? $delivery['total_delivery_price'] : 0;
            }

            if (! empty($validated['promo_code'])) {
                $response = $this->promoCodeService->check($validated['promo_code'], true);

                if ($response->getData(true)['status_code'] == 200) {
                    $promoData = $response->getData(true)['data'];

                    if (($promoData['discount_percent'] ?? 0) > 0) {
                        $discountAmount = ($validated['total_price'] * $promoData['discount_percent']) / 100;
                        $validated['total_price'] = round($validated['total_price'] - $discountAmount, 2);
                    }

                    $appliedPromoId = $promoData['id'];
                    if (! $requiresCardPayment) {
                        $this->promoCodeService->applyPromoCodeToUser($appliedPromoId, $user->id, true);
                        $this->promoCodeService->decrementPromoCodeCount($appliedPromoId);
                    }
                } else {
                    return responseHelper($response->getData(true)['message'], $response->getData(true)['status_code']);
                }
                unset($validated['promo_code']);
            }

            if ($pay_with_balance) {
                //    $userBalance = $this->balanceService->getBalance()->getData(true)['data']['balance'] ?? 0;
                $userBalance = $this->balanceService->getBalance()->getData(true)['balance'] ?? 0;

                if ($userBalance < ($validated['total_price'] + $validated['shipping_price'])) {
                    return responseHelper(__('Insufficient balance to complete the order.'), 403);
                }

                $balanceResponse = $this->balanceService->withdraw(
                    $user->id,
                    ($validated['total_price'] + $validated['shipping_price']),
                    "Payment for order with transaction ID: {$validated['transaction_id']}"
                );

                $balanceContent = $balanceResponse->getData(true);

                if (! ($balanceContent['success'] && $balanceContent['status_code'] === 200)) {
                    return responseHelper(__('Failed to process payment from balance. Please try again.'), 403);
                }
                $validated['paid_at'] = now();
                $validated['payment_type'] = 'BALANCE';

                Basket::where('user_id', $user->id)
                    ->whereIn('id', $basket->pluck('id'))
                    ->update(['is_ordered' => true, 'transaction_id' => $validated['transaction_id']]);

            }
            $data = handleTransaction(
                fn () => $this->model->create($validated)->refresh(),
                'Order added successfully.',
                null,
                201
            );

            $content = $data->getData(true);

            if ($content['status_code'] === 201) {
                $orderId = (int) $content['data']['id'];

                handleTransaction(fn () => OrderStatus::create([
                    'order_id' => $orderId,
                    'status' => $requiresCardPayment ? OrderStatusEnum::WAITING_PAYMENT : OrderStatusEnum::PLACED,
                ]));

                foreach ($basket as $item) {
                    $product = $item->product;

                    $currentUnitPrice = $this->pricingService
                        ->prices($product, $item->size_id, null, $validated['pricing_type'])['final_price'];

                    handleTransaction(fn () => OrderItem::create([
                        'order_id' => $orderId,
                        'product_id' => $product->id,
                        'color_id' => $item->color_id,
                        'size_id' => $item->size_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $currentUnitPrice,
                        'total_price' => $currentUnitPrice * $item->quantity,
                    ]));

                    $affected = Product::where('id', $product->id)
                        ->where('stock_count', '>=', $item->quantity)
                        ->decrement('stock_count', $item->quantity);

                    if ($affected) {
                        $product->increment('sales_count', $item->quantity);
                    }
                }

                $createdOrder = Order::with('items.product')->findOrFail($orderId);

                // A card order is still on the payment page, so it is told
                // about only once the payment lands.
                if (! $requiresCardPayment) {
                    app(OrderStatusNotifier::class)->notify($createdOrder, OrderStatusEnum::PLACED);
                }

                if (! $requiresCardPayment) {
                    Basket::where('user_id', $user->id)
                        ->whereIn('id', $basket->pluck('id'))
                        ->update(['is_ordered' => true, 'transaction_id' => $validated['transaction_id']]);
                }

                if ($requiresCardPayment) {
                    Basket::where('user_id', $user->id)
                        ->whereIn('id', $basket->pluck('id'))
                        ->update(['transaction_id' => $validated['transaction_id']]);
                }
                //  Basket::destroy($basket->pluck('id')->toArray());

                if ($appliedPromoId) {
                    \DB::table('used_promo_codes')
                        ->where('promo_code_id', $appliedPromoId)
                        ->where('user_id', $user->id)
                        ->whereNull('order_id')
                        ->update(['order_id' => $orderId]);
                }
            }
            $paymentUrl = '';

            if ($requiresCardPayment) {

                //                $paymentResponse = EPointService::widgetPay(
                //                    config('app.epoint_private_key'),
                //                    config('app.epoint_public_key'),
                //                    $validated['total_price'] + $validated['shipping_price'],
                //                    $validated['transaction_id'],
                //                    "Payment for order #{$orderId}",
                //                );

                //                $paymentResponse = EPointService::saveCardAndPayment(
                //                    config('app.epoint_private_key'),
                //                    config('app.epoint_public_key'),
                //                    $orderId,
                //                    $validated['total_price'] + $validated['shipping_price'],
                //                    "Payment for order #{$orderId}",
                //                    route('api.payment.success', ['transaction_id' => $validated['transaction_id']]),
                //                    route('api.payment.error', ['transaction_id' => $validated['transaction_id']])
                //
                //                );

                if ($appliedPromoId) {
                    $this->promoCodeService->applyPromoCodeToUser($appliedPromoId, $user->id, true, null, $validated['transaction_id']);
                }

                $paymentUrl = route('api.payment.start', [
                    'order_id' => $validated['transaction_id'],
                    'price' => $validated['total_price'] + $validated['shipping_price'],
                    'lang' => app()->getLocale(),
                ]);
                \Log::error('paymentUrl', [$paymentUrl]);
                //                $paymentUrl = $paymentResponse->redirect_url ?? '';
                //                $paymentUrl = $paymentResponse->widget_url ?? '';
            }

            return responseHelper($requiresCardPayment ? 'Order redirected to payment page.' : 'Order added successfully.', 200, [
                'payment_url' => $paymentUrl,
                'pay_with_balance' => $pay_with_balance,
                'payment_type' => $requestedPaymentType,
                'transaction_id' => $validated['transaction_id'],
            ]);

        } catch (\Throwable $e) {
            if ($appliedPromoId) {
                \DB::table('used_promo_codes')
                    ->where('promo_code_id', $appliedPromoId)
                    ->where('user_id', $user->id)
                    ->whereNull('order_id')
                    ->delete();
            }

            \Log::error('Order creation failed', [
                'user_id' => $user->id,
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(10)->toArray(),
            ]);

            return responseHelper(__('Something went wrong while placing the order.'), 403, [
                'payment_url' => '',
                'transaction_id' => $validated['transaction_id'] ?? '',
            ]);
        } finally {
            $lock->release();
        }
    }

    public function previewOrder($request): JsonResponse
    {
        $user = auth()->user();
        $addressType = $request->input('address_type', 'STANDARD');
        $address_id = $request->input('address_type_id', $request->input('address_id', null));

        try {
            $deliveryPrice = 0;
            $deliveryCity = '';
            $freeFrom = 0;
            $deliveryInfoData = ['price' => 0, 'free_from' => 0];

            $basket = Basket::with(['product.sizes'])
                ->where('user_id', $user->id)
                ->where('selected', true)
                ->where('is_ordered', false)
                ->get();

            if ($basket->isEmpty()) {
                return responseHelper(__('Your basket is empty.'), 403);
            }

            $pricingSummary = $this->pricingService->basketPricingSummary(
                $basket,
                $this->settingsService->getWholesaleMinimalPurchasePrice()
            );

            if ($addressType === 'PICKUP_POINT') {
                $pickupPoint = PickupPoint::query()->where('is_active', true)->find($address_id);
                if (! $pickupPoint) {
                    $pickupPoint = PickupPoint::query()->where('is_active', true)->first();
                }
                if ($pickupPoint) {
                    $deliveryCity = $pickupPoint->name;
                    $deliveryPrice = (float) $pickupPoint->price;
                    $freeFrom = 0;
                    $deliveryInfoData = [
                        'price' => (float) $pickupPoint->price,
                        'free_from' => 0,
                    ];
                }
            } elseif ($addressType === 'TAKE_FROM_STORE') {
                $deliveryCity = 'Snaker';
                $deliveryPrice = 0;
                $freeFrom = 0;
                $deliveryInfoData = [
                    'price' => 0,
                    'free_from' => 0,
                ];
            } else {
                $address = Address::where('user_id', $user->id)
                    ->when($address_id, fn ($q) => $q->where('id', $address_id),
                        fn ($q) => $q->where('is_default', true))
                    ->first();

                if (! $address) {
                    return responseHelper(__('Please set a valid default address before placing an order.'), 403);
                }

                if (! City::findMatching($address->city)) {
                    return responseHelper(__('Delivery is no longer available for this address. Please select a new address.'), 403);
                }

                $deliveryResponse = $this->deliveryService
                    ->details(null, $address->city)
                    ->getData(true);

                $delivery = $deliveryResponse['data'] ?? null;
                if (! $delivery) {
                    return responseHelper(__('Delivery service is not available for your city.'), 403);
                }

                $deliveryCity = $address->city;
                if ($addressType === 'STANDARD_FAST') {
                    $deliveryPrice = (float) ($delivery['fast_price'] ?? 0);
                    $freeFrom = 0;
                } else {
                    $freeFrom = (float) ($delivery['free_from'] ?? INF);
                    $deliveryPrice = $pricingSummary['final_total'] < $freeFrom
                        ? (float) ($delivery['price'] ?? 0)
                        : 0;
                }
                $deliveryInfoData = [
                    'price' => (float) ($delivery['price'] ?? 0),
                    'free_from' => (float) ($delivery['free_from'] ?? 0),
                ];
            }

            $items = [];

            foreach ($basket as $item) {
                $product = $item->product;
                if (! $product) {
                    continue;
                }

                $prices = $this->pricingService->prices($product, $item->size_id, null, $pricingSummary['pricing_type']);
                $originalPrice = $prices['original_price'];
                $finalUnitPrice = $prices['final_price'];

                $quantity = (int) $item->quantity;
                $subtotalOriginal = $originalPrice * $quantity;
                $subtotalFinal = $finalUnitPrice * $quantity;

                $items[] = [
                    'id' => $item->id,
                    'title' => is_array($product->title) ? ($product->title['az'] ?? reset($product->title)) : $product->title,
                    'quantity' => $quantity,
                    'original_price' => round($originalPrice, 2),
                    'discounted_price' => round($finalUnitPrice, 2),
                    'total' => round($subtotalFinal, 2),
                    'retail_price' => round($this->pricingService->retailPrices($product, $item->size_id)['final_price'], 2),
                    'wholesale_price' => round($this->pricingService->wholesalePrices($product, $item->size_id)['final_price'], 2),
                ];
            }

            $total = $pricingSummary['final_total'] + $deliveryPrice;

            return responseHelper(__('Order preview calculated successfully.'), 200, [
                'items' => $items,
                'main_amount' => $pricingSummary['original_total'],
                'discount_amount' => $pricingSummary['discount_amount'],
                'discounted_total' => $pricingSummary['final_total'],
                'delivery' => round($deliveryPrice, 2),
                'total' => round($total, 2),
                'pricing_type' => $pricingSummary['pricing_type'],
                'is_wholesale_applied' => $pricingSummary['is_wholesale_applied'],
                'retail_total' => $pricingSummary['retail_total'],
                'wholesale_total' => $pricingSummary['wholesale_total'],
                'wholesale_minimal_purchase_price' => $pricingSummary['wholesale_minimal_purchase_price'],
                'wholesale_remaining_amount' => $pricingSummary['wholesale_remaining_amount'],
                'city' => $deliveryCity,
                'delivery_info' => $deliveryInfoData,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Order preview failed: '.$e->getMessage());

            return responseHelper(__('Something went wrong while preparing the order preview.'), 500);
        }
    }

    public function buyOne($request, $product_id)
    {
        $validated = $request->validated();
        $color_id = $request->color_id ?? null;
        $size_id = $request->size_id ?? null;
        $address_id = $request->address_id ?? null;

        if ($color_id) {
            unset($validated['color_id']);
        }
        if ($size_id) {
            unset($validated['size_id']);
        }
        if ($address_id) {
            unset($validated['address_id']);
        }

        $appliedPromoId = null;

        try {
            $address = Address::where('user_id', auth()->id())
                ->when($address_id, fn ($q) => $q->where('id', $address_id),
                    fn ($q) => $q->where('is_default', true))
                ->first();

            if (! $address) {
                return responseHelper(__('Please set a valid default address with a city before placing an order.'), 403);
            }

            if (! City::findMatching($address->city)) {
                return responseHelper(__('Delivery is no longer available for this address. Please select a new address.'), 403);
            }

            $deliveryResponse = $this->deliveryService
                ->details(null, $address->city)
                ->getData(true);

            $delivery = $deliveryResponse['data'] ?? null;

            if (! $delivery) {
                return responseHelper(__('Delivery service is not available for your city.'), 403);
            }

            $product = Product::publiclyAvailable()->findOrFail($product_id);

            if ($color_id && ! $product->colors()->where('color_id', $color_id)->exists()) {
                return responseHelper(__('Selected color is not available for this product.'), 403);
            }

            if ($size_id && ! $product->sizes()->where('size_id', $size_id)->exists()) {
                return responseHelper(__('Selected size is not available for this product.'), 403);
            }

            if ($product->stock_count < 1) {
                return responseHelper("Insufficient stock for product: {$product->title['az']}", 403);
            }

            $validated['address_id'] = $address->id;
            $validated['user_id'] = auth()->id();
            $validated['transaction_id'] = (string) strtoupper(Str::uuid());

            $price = $product->discount && $product->discount > 0 ? $product->discount : $product->price;

            $validated['total_price'] = round($price, 2);
            $validated['discount_price'] = $product->discount && $product->discount > 0 ? round($product->price - $product->discount, 2) : 0;
            $validated['shipping_price'] = $validated['total_price'] < $delivery['free_from'] ? ($delivery['price'] ?? 0) : 0;

            // ========================
            // PROMO CODE
            // ========================
            $promoData = null;
            if (! empty($validated['promo_code'])) {

                if ($product->discount && $product->discount > 0) {
                    return responseHelper(__('Promo codes cannot be applied to already discounted products.'), 403);
                }

                $response = $this->promoCodeService->check($validated['promo_code'], true);

                if ($response->getData(true)['status_code'] !== 200) {
                    return responseHelper($response->getData(true)['message'], $response->getData(true)['status_code']);
                }

                $promoData = $response->getData(true)['data'];

                if (! empty($promoData['discount_percent']) && $promoData['discount_percent'] > 0) {
                    $discountAmount = ($product->price * $promoData['discount_percent']) / 100;
                    $validated['total_price'] = round($validated['total_price'] - $discountAmount, 2);
                }

                $appliedPromoId = $promoData['id'];
                $this->promoCodeService->applyPromoCodeToUser($appliedPromoId, $validated['user_id'], true);

                unset($validated['promo_code']);
            }

            // ========================
            // PAYMENT WITH BALANCE
            // ========================
            if (! empty($validated['pay_with_balance']) && $validated['pay_with_balance']) {
                $userBalance = $this->balanceService->getBalance()->getData(true)['data']['balance'] ?? 0;

                if ($userBalance < ($validated['total_price'] + $validated['shipping_price'])) {
                    return responseHelper(__('Insufficient balance to complete the order.'), 403);
                }

                $balanceResponse = $this->balanceService->withdraw(
                    $validated['user_id'],
                    ($validated['total_price'] + $validated['shipping_price']),
                    "Payment for order with transaction ID: {$validated['transaction_id']}"
                );

                $balanceContent = $balanceResponse->getData(true);

                // Inverted, and both ways round it was wrong. withdraw() answers
                // 200 on success, so a successful payment satisfied
                // `success && 200 !== 201` and bailed out AFTER the money had
                // left the customer's balance, with no order to show for it —
                // while a failed withdrawal short-circuited on `success` being
                // false and fell through to create a paid order nobody paid for.
                // This is the same guard orderFromBasket has always used.
                if (! ($balanceContent['success'] && (int) $balanceContent['status_code'] === 200)) {
                    return responseHelper(__('Failed to process payment from balance. Please try again.'), 403);
                }
                unset($validated['pay_with_balance']);
            } else {
                return 'https://www.google.com';
            }

            $validated['paid_at'] = now();

            // ========================
            // ORDER CREATE
            // ========================
            $data = handleTransaction(
                fn () => $this->model->create($validated)->refresh(),
                'Order added successfully.',
                null,
                201
            );

            $content = $data->getData(true);

            if ($content['status_code'] === 201) {
                $orderId = (int) $content['data']['id'];

                handleTransaction(fn () => OrderStatus::create([
                    'order_id' => $orderId,
                    'status' => OrderStatusEnum::PLACED,
                ])->refresh());

                $product->decrement('stock_count', 1);
                $product->increment('sales_count', 1);

                handleTransaction(fn () => OrderItem::create([
                    'order_id' => $orderId,
                    'product_id' => $product->id,
                    'color_id' => $color_id,
                    'size_id' => $size_id,
                    'quantity' => 1,
                    'unit_price' => $price,
                    'total_price' => $price,
                ]));

                // ========================
                // PROMO CODE PIVOT UPDATE
                // ========================
                if ($appliedPromoId) {
                    \DB::table('used_promo_codes')
                        ->where('promo_code_id', $appliedPromoId)
                        ->where('user_id', $validated['user_id'])
                        ->whereNull('order_id')
                        ->update(['order_id' => $orderId]);
                }

                app(OrderStatusNotifier::class)->notifyById($orderId, OrderStatusEnum::PLACED);
            }
            if (empty($validated['pay_with_balance'])) {
                return 'https://www.google.com';
            }

            return responseHelper(__('Order added successfully.'), 201);

        } catch (\Throwable $e) {

            // ========================
            // PROMO CODE ROLLBACK
            // ========================
            if (! empty($appliedPromoId)) {
                \DB::table('used_promo_codes')
                    ->where('promo_code_id', $appliedPromoId)
                    ->where('user_id', $validated['user_id'])
                    ->whereNull('order_id')
                    ->delete();
            }

            \Log::error('Order creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return responseHelper(__('Something went wrong while placing the order.'), 403);
        }
    }

    public function update(int $id, $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $returnToBalance = $data['return_to_balance'] ?? false;

            $newStatus = OrderStatusEnum::resolve($data['status']);

            // Scoped to the caller unless they are staff. Without this a signed
            // in shopper could pass any order id at all and drive somebody
            // else's order — cancelling it, refunding its owner and reversing
            // the merchant's earnings. The lookup is the right place for the
            // check: a 404 tells an attacker nothing about whether the id
            // exists.
            $order = Order::with(['statuses', 'items.product'])
                ->when(
                    ! auth()->user()?->hasAnyRole(['admin', 'manager', 'developer']),
                    fn ($query) => $query->where('user_id', auth()->id()),
                )
                ->findOrFail($id);

            $cardId = $order->card_id ?? DB::table('user_saved_cards')->where('user_id', $order->user_id)->latest('created_at')->value('card_id');

            $rawCurrentStatus = $order->statuses()->latest('id')->value('status');
            $currentStatus = OrderStatusEnum::resolve($rawCurrentStatus);

            $user = auth()->user();
            $isCustomer = ! $user->hasAnyRole(['admin', 'manager', 'developer']);

            if ($newStatus === OrderStatusEnum::CANCELLED && $isCustomer && ! $currentStatus->canBeCancelledByCustomer()) {
                return responseHelper(__('Sifariş artıq qəbul edilib, ləğv edilə bilməz.'), 422);
            }

            if ($isCustomer) {

                if ($currentStatus === OrderStatusEnum::PLACED) {
                    $refundAmount = $order->payment_type === 'CASH' ? 0 : $order->total_price + $order->shipping_price;

                    if ($order->payment_type === 'CASH') {
                        // Nağd sifarişdə müştəridən hələ pul alınmayıb.
                    } elseif ($returnToBalance) {
                        $balanceData = [
                            'user_id' => $order->user_id,
                            'type' => BalanceType::REFUND->value,
                            'amount' => (float) $refundAmount,
                            'note' => "Refund for cancelled order #{$order->id}",
                        ];

                        DB::transaction(function () use ($balanceData) {
                            return Balance::create($balanceData)->refresh();
                        });
                    } else {
                        if ($order->payment_type === 'BALANCE') {
                            return responseHelper(__('You paid with your balance, but you are trying to withdraw to a card. Refunds can only be issued to your balance.'),
                                403
                            );
                        }

                        //                        $cancelResponse = EPointService::refund(
                        //                            config('app.epoint_private_key'),
                        //                            config('app.epoint_public_key'),
                        //                            $cardId,
                        //                            $order->id,
                        //                            (float)$refundAmount,
                        //                            "Refund for cancelled order #{$order->id}"
                        //                        );

                        $cancelResponse = EPointService::cancel(
                            config('app.epoint_private_key'),
                            config('app.epoint_public_key'),
                            $order->e_point_transaction,
                            (float) $refundAmount,
                        );

                        if (isset($cancelResponse->status) && (string) $cancelResponse->status === 'failed') {
                            return responseHelper(
                                $cancelResponse->message ?? 'Failed to refund the order.',
                                403,
                            );
                        }
                        \Log::error('Epoint cancel response', (array) $cancelResponse);
                    }

                    $cancelledStatus = OrderStatusEnum::CANCELLED;

                    OrderStatus::create([
                        'order_id' => $order->id,
                        'status' => $cancelledStatus->value,
                    ]);

                    foreach ($order->items as $item) {
                        if ($item->product) {
                            $item->product->increment('stock_count', $item->quantity);
                            $item->product->decrement('sales_count', $item->quantity);
                        }
                    }

                    $this->sendOrderStatusNotificationSafely($order, $cancelledStatus);

                    return responseHelper(
                        'Order status successfully updated.',
                        200,
                    );
                } else {
                    $adminWaitingStatus = OrderStatusEnum::ADMIN_WAITING;

                    OrderStatus::create([
                        'order_id' => $order->id,
                        'status' => $adminWaitingStatus->value,
                    ]);
                    $order->update(['return_to_balance' => $returnToBalance]);

                    $this->sendOrderStatusNotificationSafely($order, $adminWaitingStatus);
                }

            }

            if ($user->hasAnyRole(['admin', 'manager', 'developer'])) {
                $statusUpdated = false;

                $response = DB::transaction(function () use ($order, $newStatus, $currentStatus, &$statusUpdated) {

                    $refundAmount = 0;

                    if ($newStatus === OrderStatusEnum::CANCELLED && $order->paid_at) {

                        $refundAmount = $order->total_price + $order->shipping_price;

                        if ($order->returnToBalance) {
                            $balanceData = [
                                'user_id' => $order->user_id,
                                'type' => BalanceType::REFUND->value,
                                'amount' => (float) $refundAmount,
                                'note' => "Refund for cancelled order #{$order->id}",
                            ];

                            DB::transaction(function () use ($balanceData) {
                                return Balance::create($balanceData)->refresh();
                            });
                        } else {
                            if ($order->payment_type === 'BALANCE') {
                                return responseHelper(__('You paid with your balance, but you are trying to withdraw to a card. Refunds can only be issued to your balance.'),
                                    403
                                );
                            }
                            //                            $cancelResponse = EPointService::refund(
                            //                                config('app.epoint_private_key'),
                            //                                config('app.epoint_public_key'),
                            //                                $cardId,
                            //                                $order->id,
                            //                                (float)$refundAmount,
                            //                                "Refund for cancelled order #{$order->id}"
                            //                            );

                            $cancelResponse = EPointService::cancel(
                                config('app.epoint_private_key'),
                                config('app.epoint_public_key'),
                                $order->e_point_transaction,
                                (float) $refundAmount,
                            );

                            \Log::error('Epoint cancel response', (array) $cancelResponse);
                            if (isset($cancelResponse->status) && (string) $cancelResponse->status === 'failed') {
                                return responseHelper(
                                    $cancelResponse->message ?? 'Failed to refund the order.',
                                    403,
                                );
                            }
                        }

                    }

                    if ($newStatus === OrderStatusEnum::CANCELLED && $currentStatus !== OrderStatusEnum::CANCELLED) {
                        foreach ($order->items as $item) {
                            if ($item->product) {
                                $item->product->increment('stock_count', $item->quantity);
                                $item->product->decrement('sales_count', $item->quantity);
                            }
                        }
                    }

                    OrderStatus::create([
                        'order_id' => $order->id,
                        'status' => $newStatus->value,
                    ]);
                    $statusUpdated = true;

                    return responseHelper(
                        'Order status successfully updated.',
                        200,
                        [
                            'order_id' => $order->id,
                            'old_status' => $currentStatus->name,
                            'new_status' => $newStatus->name,
                            'refunded' => $refundAmount > 0,
                            'refund_amount' => $refundAmount,
                        ]
                    );
                });

                if ($statusUpdated) {
                    $this->sendOrderStatusNotificationSafely($order, $newStatus);
                }

                return $response;
            }

            return responseHelper(__('Status updated successfully.'), 200);
        } catch (ModelNotFoundException) {
            return responseHelper('Order not found.', 404);
        }
    }

    private function sendOrderStatusNotificationSafely(Order $order, OrderStatusEnum $status): void
    {
        // One implementation shared with the payment callback, the courier
        // webhook and the unpaid-order sweeper.
        app(OrderStatusNotifier::class)->notify($order, $status);
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $order = $this->model
                ->with('latestStatus')
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            $currentStatus = $order->latestStatus?->status;

            if (! $currentStatus || ! $currentStatus->canBeCancelledByCustomer()) {
                return responseHelper(__('Sifariş artıq qəbul edilib, ləğv edilə bilməz.'), 422);
            }

            return handleTransaction(
                fn () => tap($order)->delete(),
                'Order deleted successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Order not found.'),
            ], 404);
        }
    }

    public function getReceipt(int $orderId, $userId = null, $message = null): JsonResponse
    {
        try {
            $order = Order::with(['items.product', 'address'])
                ->where('id', $orderId)
                ->where('user_id', $userId ?? auth()->id())
                ->first();

            if (! $order) {
                return responseHelper(__('Order not found.'), 403);
            }

            $usedPromoCode = \DB::table('used_promo_codes')
                ->where('order_id', $orderId)
                ->where('user_id', $userId ?? auth()->id())
                ->first();

            $promoCodeData = null;
            if ($usedPromoCode?->promo_code_id) {
                $promoCodeData = $this->promoCodeService->details($usedPromoCode->promo_code_id, true);
            }

            $itemsTotal = round((float) $order->items->sum(fn ($item) => (float) $item->total_price), 2);
            $discountPrice = round((float) ($order->discount_price ?? 0), 2);

            $orderSummary = [
                'order_id' => $order->id,
                'transaction_id' => $order->transaction_id,
                'order_time' => $order->created_at->format('Y-m-d H:i:s'),
                'items_totals' => round($itemsTotal + $discountPrice, 2),
                'items_discounts' => (string) $discountPrice,
                'promo_code' => $promoCodeData['code'] ?? null,
                'promo_code_discount_percentage' => $promoCodeData['discount_percent'] ?? null,
                'shipping' => $order->shipping_price ?? 0,
                'total' => round((float) $order->total_price + (float) ($order->shipping_price ?? 0), 2),
            ];

            $pickup = [
                'city' => $order->address->city ?? null,
                'town' => $order->address->town_village_district ?? null,
                'street' => $order->address->street_building_number ?? null,
                'apartment' => $order->address->unit_floor_apartment ?? null,
                'phone' => $order->address->contact_number ?? auth()->user()->phone,
            ];

            return responseHelper($message ?? 'Order receipt generated successfully.', 200, [
                'order_summary' => $orderSummary,
                'pickup' => $pickup,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Order receipt failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return responseHelper(__('Something went wrong while generating the receipt.'), 403);
        }
    }

    public function downloadReceipt(int $orderId)
    {
        try {
            $user = auth()->user();

            $order = Order::with(['items.product', 'address'])
                ->where('id', $orderId)
                ->where('user_id', $user->id)
                ->first();

            if (! $order) {
                return responseHelper(__('Order not found.'), 403);
            }

            $usedPromo = \DB::table('used_promo_codes')
                ->where('user_id', $user->id)
                ->where('order_id', $order->id)
                ->first();

            $promoData = null;
            if ($usedPromo) {
                $promo = PromoCode::find($usedPromo->promo_code_id);
                if ($promo) {
                    $promoData = [
                        'code' => $promo->code,
                        'discount_percent' => $promo->discount_percent,
                    ];
                }
            }

            $itemsTotal = round((float) $order->items->sum(fn ($item) => (float) $item->total_price), 2);
            $discountPrice = round((float) ($order->discount_price ?? 0), 2);

            $orderSummary = [
                'order_id' => $order->id,
                'transaction_id' => $order->transaction_id,
                'order_time' => $order->created_at->format('Y-m-d H:i:s'),
                'items_totals' => round($itemsTotal + $discountPrice, 2),
                'items_discounts' => $discountPrice,
                'shipping' => $order->shipping_price ?? 0,
                'total' => round((float) $order->total_price + (float) ($order->shipping_price ?? 0), 2),
                'promo' => $promoData,
            ];

            $pickup = [
                'city' => $order->address->city ?? null,
                'town' => $order->address->town_village_district ?? null,
                'street' => $order->address->street_building_number ?? null,
                'apartment' => $order->address->unit_floor_apartment ?? null,
                'phone' => $order->address->contact_number ?? $user->phone,
            ];

            foreach ($order->items as $item) {
                $item->product_title = mb_convert_encoding(
                    $item->product->getTranslation('title', app()->getLocale()),
                    'UTF-8', 'UTF-8'
                );
            }

            $htmlContent = receiptPdf($order, $pickup, $orderSummary);

            $pdf = Pdf::loadHTML($htmlContent);

            $filename = "receipt_order_{$order->transaction_id}.pdf";
            $path = storage_path("app/public/receipts/{$filename}");

            if (! file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $pdf->save($path);

            $link = asset("storage/receipts/{$filename}");

            return response()->json([
                'success' => true,
                'file_link' => $link,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Receipt PDF generation failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return responseHelper(__('Something went wrong while generating the receipt PDF.'), 403);
        }
    }

    public function calculateDeliveryPrice($addressType, $addressTypeId = null): JsonResponse
    {
        $delivery_price = 0;
        $delivery_time = '';
        $delivery_info = '';
        $free_from = 0;

        $user = auth()->user();

        $basket = Basket::with(['product.sizes'])
            ->where('user_id', $user->id)
            ->where('selected', true)
            ->where('is_ordered', false)
            ->get();

        if ($basket->isEmpty()) {
            return responseHelper(__('Your basket is empty.'), 403);
        }

        $pricingSummary = $this->pricingService->basketPricingSummary(
            $basket,
            $this->settingsService->getWholesaleMinimalPurchasePrice()
        );
        $total_price = $pricingSummary['final_total'];

        switch ($addressType) {
            case 'STANDARD':
                //                $address = $this->addressService->details($addressTypeId);
                $address = Address::where('id', $addressTypeId)
                    ->where('user_id', $user->id)
                    ->first();
                $delivery_info = $this->getDeliveryDescriptionByType('STANDARD');

                if (! $address) {
                    return responseHelper(__('Please select a valid delivery address.'), 403);
                }

                $city = $address->city;

                if (! City::findMatching($city)) {
                    return responseHelper(__('Delivery is no longer available for this address. Please select a new address.'), 403);
                }

                $res = $this->deliveryService->details(null, $city);
                if (is_object($res) && method_exists($res, 'status') && $res->status() === 200) {
                    $data = $res->getData(true);
                    $delivery_price = $data['price'] ?? ($data['data']['price'] ?? 0);
                    $free_from = $data['free_from'] ?? ($data['data']['free_from'] ?? 0);
                    $delivery_time = $data['delivery_time'] ?? ($data['data']['delivery_time'] ?? '');

                    $delivery_time = $delivery_time."\n".$delivery_info;
                    //                        $delivery_time = (($data['delivery_time'] ?? $data['data']['delivery_time'] ?? null))
                    //                            ? ($data['delivery_time'] ?? $data['data']['delivery_time']) . "\n" . $delivery_info
                    //                            : "";
                    $delivery_price = $total_price < $free_from ? $delivery_price : 0;

                } else {
                    return responseHelper(__('Delivery service is not available for your city.'), 403);
                }

                break;

            case 'STANDARD_FAST':
                //                $address = $this->addressService->details($addressTypeId);
                $address = Address::where('id', $addressTypeId)
                    ->where('user_id', $user->id)
                    ->first();
                $delivery_info = $this->getDeliveryDescriptionByType('STANDARD_FAST');

                if (! $address) {
                    return responseHelper(__('Please select a valid delivery address.'), 403);
                }

                $city = $address->city;

                if (! City::findMatching($city)) {
                    return responseHelper(__('Delivery is no longer available for this address. Please select a new address.'), 403);
                }

                $res = $this->deliveryService->details(null, $city);
                if (is_object($res) && method_exists($res, 'status') && $res->status() === 200) {
                    $data = $res->getData(true);
                    $delivery_price = $data['fast_price'] ?? ($data['data']['fast_price'] ?? 0);
                    $free_from = $data['free_from'] ?? ($data['data']['free_from'] ?? 0);
                    $delivery_time = $data['fast_delivery_time'] ?? ($data['data']['fast_delivery_time'] ?? '');
                    $delivery_time = $delivery_time."\n".$delivery_info;
                    //                        $delivery_price = $total_price < $free_from ? $delivery_price : 0;

                } else {
                    return responseHelper(__('Delivery service is not available for your city.'), 403);
                }

                break;

            case 'PICKUP_POINT':
                $res = $this->pickupPointService->details($addressTypeId);
                $delivery_info = $this->getDeliveryDescriptionByType('PICKUP_POINT');

                if (is_object($res) && method_exists($res, 'status')) {
                    $status = $res->status();
                    $data = $res->getData(true);

                    if ($status === 200) {
                        $delivery_price = (float) ($data['price'] ?? ($data['data']['price'] ?? ($data['original']['price'] ?? 0)));
                        $delivery_time = $data['delivery_time'] ?? ($data['data']['delivery_time'] ?? ($data['original']['delivery_time'] ?? ''));
                        $delivery_time = $delivery_time."\n".$delivery_info;
                        $free_from = 0;
                    } else {
                        return responseHelper(__('This pickup point is no longer available. Please select another pickup point.'), 403);
                    }
                }
                break;

            case 'TAKE_FROM_STORE':
                $delivery_price = 0;
                $delivery_info = $this->getDeliveryDescriptionByType('TAKE_FROM_STORE');
                $delivery_time = $delivery_time."\n".$delivery_info;

                break;

            default:
                \Log::error('Unknown Address Type:', ['type' => $addressType]);
                break;
        }

        $finalData = [
            'total_delivery_price' => (float) $delivery_price,
            'delivery_time' => (string) $delivery_time,
            'free_from' => (float) $free_from,
            'delivery_info' => (string) $delivery_info,
        ];

        return responseHelper(__('Delivery price calculation successful.'), 200, $finalData);
    }

    private function getDeliveryDescriptionByType(string $type): string
    {
        $response = $this->deliveryInfoService->getByType($type);
        $info = $response->getData();

        if (! $info || ! isset($info->description)) {
            return '';
        }

        return $info->description->{app()->getLocale()} ?? ($info->description->en ?? '');
    }
}
