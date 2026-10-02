<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus as OrderStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderStatus;
use Modules\Order\Http\Requests\OrderUpdateRequest;
use Modules\Order\Services\OrderService;

class OrderController extends AdminController
{
    protected string $title = 'Sifarişlər';

    public function __construct(private readonly OrderService $service) {}

    public function index(Request $request)
    {
        $this->requirePermission('view orders-admin');

        $query = Order::query()->with(['user', 'latestStatus', 'items'])->latest('id');

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

        $rows = $query->paginate(20)->withQueryString();

        if ($this->isHtmx($request)) {
            return view('admin.pages.orders._table', $this->tableData($rows, $request));
        }

        return view('admin.pages.orders.index', array_merge($this->tableData($rows, $request), [
            'title' => $this->title,
            'statuses' => OrderStatusEnum::cases(),
            'filters' => $request->only(['q', 'status']),
        ]));
    }

    public function show(int $id)
    {
        $this->requirePermission('view orders-admin');

        $order = Order::query()
            ->with([
                'user',
                'address',
                'latestStatus',
                'statuses' => fn ($q) => $q->orderByDesc('id'),
                'items.product.images',
                'items.product.category',
            ])
            ->findOrFail($id);

        return view('admin.pages.orders.show', [
            'title' => 'Sifariş #'.$order->id,
            'order' => $order,
            'statuses' => OrderStatusEnum::cases(),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $this->requirePermission('update order');

        $order = Order::query()->findOrFail($id);
        $admin = admin_user();

        // OrderService authorises writes against auth()->user(); authenticate the
        // admin on the default guard for the duration of the call so the exact
        // same refund/stock/notification flow runs.
        $previous = Auth::guard('web')->user();
        Auth::guard('web')->setUser($admin);

        try {
            $form = OrderUpdateRequest::createFrom($request);
            $form->setContainer(app());
            $form->setRedirector(app('redirect'));
            $form->validateResolved();

            $response = $this->service->update($order->id, $form);
        } finally {
            if ($previous) {
                Auth::guard('web')->setUser($previous);
            } else {
                Auth::guard('web')->forgetUser();
            }
        }

        $payload = method_exists($response, 'getData') ? $response->getData(true) : null;
        $message = is_array($payload) ? ($payload['message'] ?? 'Status yeniləndi.') : 'Status yeniləndi.';

        if ($request->header('HX-Request')) {
            return response('', 204)->header('HX-Trigger', $this->htmxTriggers([
                'toast' => ['type' => 'success', 'message' => $message],
                'order:refresh' => true,
            ]));
        }

        return redirect()->route('admin.orders.show', $order->id)->with('status', $message);
    }

    public function destroy(int $id)
    {
        $this->requirePermission('delete order');

        $order = Order::query()->with('items')->findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return redirect()->route('admin.orders.index')->with('status', __('Sifariş silindi.'));
    }

    private function tableData($rows, Request $request): array
    {
        return [
            'rows' => $rows,
            'statuses' => OrderStatusEnum::cases(),
            'filters' => $request->only(['q', 'status']),
        ];
    }
}
