<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use Illuminate\Http\Request;
use Modules\Product\Services\ReviewService;

class ReviewController extends AdminController
{
    protected string $title = 'Şərhlər';

    private function service(): ReviewService
    {
        return app(ReviewService::class);
    }

    public function index(Request $request)
    {
        $this->requirePermission('view products');

        $rows = $this->service()->adminQuery($request)->paginate(20)->withQueryString();

        if ($this->isHtmx($request)) {
            return view('admin.pages.reviews._table', ['rows' => $rows]);
        }

        return view('admin.pages.reviews.index', [
            'title' => $this->title,
            'rows' => $rows,
            'statuses' => ReviewStatus::cases(),
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $this->requirePermission('update product');

        $data = $request->validate(['status' => ['required', 'integer', 'in:0,1,2']]);

        $this->service()->setStatus($id, (int) $data['status']);

        if ($this->isHtmx($request)) {
            return response('', 204)->header('HX-Trigger', $this->htmxTriggers([
                'toast' => ['type' => 'success', 'message' => 'Şərh statusu yeniləndi.'],
            ]));
        }

        return back()->with('status', __('Şərh statusu yeniləndi.'));
    }

    public function toggleFeatured(int $id)
    {
        $this->requirePermission('update product');

        $featured = $this->service()->toggleFeatured($id);

        return back()->with('status', $featured
            ? __('Şərh "What our client say" bölməsinə əlavə edildi.')
            : __('Şərh ana səhifədən çıxarıldı.'));
    }

    public function destroy(int $id)
    {
        $this->requirePermission('delete product');

        $this->service()->remove($id);

        return back()->with('status', __('Şərh silindi.'));
    }
}
