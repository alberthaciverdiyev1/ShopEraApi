<?php

namespace Modules\Setting\Services;

use App\Enums\OrderStatus as OrderStatusEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Delivery\Entities\City;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderItem;
use Modules\Order\Entities\OrderStatus;
use Modules\Product\Entities\Product;

class StatisticService
{
    public function statistics($request): JsonResponse
    {
        try {
            //            $fromDate = Carbon::now()->startOfYear();
            $fromDate = Carbon::now()->startOfMonth();

            $topProducts = OrderItem::query()
                ->whereHas('order', fn ($q) => $q->where('created_at', '>=', $fromDate))
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

            $topCustomers = Order::query()
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

            $cityOrders = Order::query()
                ->where('created_at', '>=', $fromDate)
                ->with('address:id,city')
                ->get()
                ->groupBy(fn ($order) => $order->address?->city ?? 'Unknown')
                ->map(fn ($orders) => $orders->count());

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

            $statusData = OrderStatus::query()
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

    /** Admin dashboard payload: counts, revenue, order status cards, recent orders. */
    public function dashboard(): array
    {
        $from = Carbon::now()->startOfMonth();

        $ordersByStatus = OrderStatus::query()
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')->from('order_statuses')->groupBy('order_id');
            })
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusCards = collect(OrderStatusEnum::cases())->map(fn ($case) => [
            'label' => $case->label(),
            'value' => $case->value,
            'count' => (int) ($ordersByStatus[$case->value] ?? 0),
        ]);

        $revenue = (float) Order::query()
            ->where('created_at', '>=', $from)
            ->whereHas('latestStatus', fn ($q) => $q->whereIn('status', [
                OrderStatusEnum::PLACED->value,
                OrderStatusEnum::PROCESSING->value,
                OrderStatusEnum::DELIVERED->value,
            ]))
            ->sum('total_price');

        $topProducts = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('created_at', '>=', $from))
            ->selectRaw('product_id, SUM(quantity) as total_sold')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(6)
            ->with('product.images')
            ->get();

        return [
            'stats' => [
                ['label' => 'Məhsullar', 'value' => Product::query()->count(), 'icon' => 'box', 'route' => route('admin.products.index')],
                ['label' => 'Sifarişlər', 'value' => Order::query()->count(), 'icon' => 'receipt', 'route' => route('admin.orders.index')],
                ['label' => 'İstifadəçilər', 'value' => \Modules\User\Entities\User::query()->count(), 'icon' => 'users', 'route' => route('admin.users.index')],
                ['label' => 'Kateqoriyalar', 'value' => \Modules\Category\Entities\Category::query()->count(), 'icon' => 'layers', 'route' => route('admin.categories.index')],
            ],
            'revenue' => $revenue,
            'statusCards' => $statusCards,
            'recentOrders' => Order::query()->with('user')->latest('id')->limit(8)->get(),
            'pendingReviews' => \Modules\Product\Entities\Review::query()->where('status', 'pending')->count(),
            'topProducts' => $topProducts,
            'monthLabel' => $from->translatedFormat('F Y'),
        ];
    }
}
