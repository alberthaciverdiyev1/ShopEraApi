<?php

namespace Modules\Setting\Services;

use App\Enums\OrderStatus as OrderStatusEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Modules\Delivery\Http\Entities\City;
use Modules\Product\Http\Entities\Product;

class StatisticService
{
    public function statistics($request): JsonResponse
    {
        try {
//            $fromDate = Carbon::now()->startOfYear();
            $fromDate = Carbon::now()->startOfMonth();

            $topProducts = \Modules\Order\Http\Entities\OrderItem::query()
                ->whereHas('order', fn($q) => $q->where('created_at', '>=', $fromDate))
                ->selectRaw('product_id, SUM(quantity) as total_sold')
                ->groupBy('product_id')
                ->orderByDesc('total_sold')
                ->limit(20)
                ->pluck('total_sold', 'product_id');

            $products = Product::query()
                ->with(['colors', 'sizes', 'images', 'category', 'brand'])
                ->withAvg('reviews', 'rate')
                ->withCount('reviews')
                ->whereIn('id', $topProducts->keys())
                ->get()
                ->map(function ($product) use ($topProducts) {
                    $product->total_sold = (int) ($topProducts[$product->id] ?? 0);
                    $product->rate = ($product->reviews_avg_rate !== null) ? round($product->reviews_avg_rate, 2) : 0;
                    $product->rate_count = $product->reviews_count;
                    $product->is_favorite = Auth::check()
                        ? $product->favoritedBy()->where('user_id', Auth::id())->exists()
                        : false;
                    return $product;
                })
                ->sortByDesc('total_sold')
                ->values();

            $topCustomers = \Modules\Order\Http\Entities\Order::query()
                ->where('created_at', '>=', $fromDate)
                ->selectRaw('user_id, COUNT(*) as orders_count, SUM(total_price) as total_spent')
                ->groupBy('user_id')
                ->orderByDesc('total_spent')
                ->with('user:id,name,email,phone')
                ->limit(100)
                ->get()
                ->map(function ($item) {
                    $user = $item->user;
                    return [
                        'id' => $user?->id,
                        'name' => $user?->name,
                        'email' => $user?->email,
                        'phone' => $user?->phone,
                        'orders_count' => (int) $item->orders_count,
                        'total_spent' => (float) $item->total_spent,
                    ];
                });

            $cityOrders = \Modules\Order\Http\Entities\Order::query()
                ->where('created_at', '>=', $fromDate)
                ->with('address:id,city')
                ->get()
                ->groupBy(fn($order) => $order->address?->city ?? 'Unknown')
                ->map(fn($orders) => $orders->count());

            $cityLabels = City::withTrashed()->pluck('name', 'key');

            $topCities = $cityOrders
                ->map(function ($ordersCount, $cityKey) use ($cityLabels) {
                    return [
                        'city' => $cityKey,
                        'city_label' => $cityLabels[$cityKey] ?? $cityKey,
                        'orders_count' => (int) $ordersCount,
                    ];
                })
                ->sortByDesc('orders_count')
                ->take(10)
                ->values();

            $discountedCount = Product::query()
                ->whereNotNull('discount')
                ->whereColumn('discount', '<', 'price')
                ->count();

            $statusData = \Modules\Order\Http\Entities\OrderStatus::query()
                ->whereIn('id', function ($query) use ($fromDate) {
                    $query->selectRaw('MAX(id)')
                        ->from('order_statuses')
                        ->whereIn('order_id', function ($q) use ($fromDate) {
                            $q->select('id')
                                ->from('orders')
                                ->where('created_at', '>=', $fromDate);
                        })
                        ->groupBy('order_id');
                })
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $statusCounts = collect(OrderStatusEnum::cases())
                ->mapWithKeys(function ($case) use ($statusData) {
                    $label = $case->label();
                    $value = $statusData[$case->value] ?? 0;
                    return [$label => (int) $value];
                });
            $data = [
                'date_range' => [
                    'from' => $fromDate->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'top_products' => $products,
                'top_customers' => $topCustomers,
                'top_cities' => $topCities,
                'discounted_products_count' => $discountedCount,
                'orders_by_status' => $statusCounts,
            ];

            return responseHelper(__('Statistics retrieved successfully.'), 200, $data);
        } catch (\Throwable $e) {
            Log::error('StatisticService::statistics failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(10)->toArray(),
            ]);

            return responseHelper(__('Something went wrong while fetching statistics.'), 500);
        }
    }
}
