<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus as OrderStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Order\Http\Requests\OrderUpdateRequest;
use Modules\Order\Services\OrderService;

class OrderController extends AdminController
{
    protected string $title = 'Sifarişlər';

    public function __construct(private readonly OrderService $service) {}

    public function index(Request $request)
    {
        $this->requirePermission('view orders-admin');

        $rows = $this->service->adminQuery($request)->paginate(20)->withQueryString();

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

        $order = $this->service->adminFind($id);

        return view('admin.pages.orders.show', [
            'title' => 'Sifariş #'.$order->id,
            'order' => $order,
            'statuses' => OrderStatusEnum::cases(),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $this->requirePermission('update order');

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

            $response = $this->service->update($id, $form);
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

        return redirect()->route('admin.orders.show', $id)->with('status', $message);
    }

    public function destroy(int $id)
    {
        $this->requirePermission('delete order');

        $this->service->adminDelete($id);

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
