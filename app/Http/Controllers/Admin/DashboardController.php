<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus as OrderStatusEnum;
use Illuminate\Support\Carbon;
use Modules\Brand\Entities\Brand;
use Modules\Category\Entities\Category;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderItem;
use Modules\Order\Entities\OrderStatus;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Review;
use Modules\User\Entities\User;

class DashboardController extends AdminController
{
    protected string $title = 'İdarə paneli';

    public function index()
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

        return view('admin.pages.dashboard', [
            'title' => $this->title,
            'stats' => [
                ['label' => 'Məhsullar', 'value' => Product::query()->count(), 'icon' => 'box', 'route' => route('admin.products.index')],
                ['label' => 'Sifarişlər', 'value' => Order::query()->count(), 'icon' => 'receipt', 'route' => route('admin.orders.index')],
                ['label' => 'İstifadəçilər', 'value' => User::query()->count(), 'icon' => 'users', 'route' => route('admin.users.index')],
                ['label' => 'Kateqoriyalar', 'value' => Category::query()->count(), 'icon' => 'layers', 'route' => route('admin.categories.index')],
            ],
            'revenue' => $revenue,
            'statusCards' => $statusCards,
            'recentOrders' => Order::query()->with('user')->latest('id')->limit(8)->get(),
            'pendingReviews' => Review::query()->where('status', 'pending')->count(),
            'topProducts' => $topProducts,
            'monthLabel' => $from->translatedFormat('F Y'),
        ]);
    }
}
