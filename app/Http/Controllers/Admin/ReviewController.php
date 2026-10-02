<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use Illuminate\Http\Request;
use Modules\Product\Entities\Review;

class ReviewController extends AdminController
{
    protected string $title = 'Şərhlər';

    public function index(Request $request)
    {
        $this->requirePermission('view products');

        $query = Review::query()->with(['user', 'product.images'])->latest('id');

        if ($request->filled('status') && is_numeric($request->query('status'))) {
            $query->where('status', (int) $request->query('status'));
        }

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($inner) use ($term) {
                $inner->where('comment', 'like', "%{$term}%")
                    ->orWhereHas('product', fn ($p) => $p->where('title->az', 'like', "%{$term}%"));
            });
        }

        $rows = $query->paginate(20)->withQueryString();

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

        $data = $request->validate([
            'status' => ['required', 'integer', 'in:0,1,2'],
        ]);

        Review::query()->findOrFail($id)->update(['status' => (int) $data['status']]);

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

        $review = Review::query()->findOrFail($id);
        $featured = ! $review->is_featured;

        $review->update([
            'is_featured' => $featured,
            // Only an approved review can appear on the storefront strip.
            'status' => $featured ? ReviewStatus::APPROVED->value : $review->status->value,
        ]);

        return back()->with('status', $featured
            ? __('Şərh "What our client say" bölməsinə əlavə edildi.')
            : __('Şərh ana səhifədən çıxarıldı.'));
    }

    public function destroy(int $id)
    {
        $this->requirePermission('delete product');

        Review::query()->findOrFail($id)->delete();

        return back()->with('status', __('Şərh silindi.'));
    }
}
