<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\Marketplace\Entities\ListingPromotion;
use Modules\Marketplace\Entities\PromotionPackage;
use Modules\Marketplace\Services\PromotionService;

class PromotionController extends AdminController
{
    protected string $title = 'İrəli çəkmə';

    public function __construct(private readonly PromotionService $service) {}

    public function index()
    {
        $this->requirePermission('update product');

        return view('admin.pages.promotions', [
            'title' => $this->title,
            'packages' => PromotionPackage::query()->orderBy('sort_order')->get(),
            'orders' => ListingPromotion::query()->with(['product', 'package', 'user'])->latest('id')->limit(100)->get(),
        ]);
    }

    public function storePackage(Request $request)
    {
        $this->requirePermission('update product');
        $data = $this->validatePackage($request);
        PromotionPackage::query()->create($this->packageData($data, $request));

        return back()->with('status', __('Paket əlavə edildi.'));
    }

    public function updatePackage(Request $request, int $id)
    {
        $this->requirePermission('update product');
        $data = $this->validatePackage($request);
        PromotionPackage::query()->findOrFail($id)->update($this->packageData($data, $request));

        return back()->with('status', __('Paket yeniləndi.'));
    }

    public function destroyPackage(int $id)
    {
        $this->requirePermission('update product');
        PromotionPackage::query()->findOrFail($id)->delete();

        return back()->with('status', __('Paket silindi.'));
    }

    public function activate(int $id)
    {
        $this->requirePermission('update product');
        $this->service->activate(ListingPromotion::query()->findOrFail($id));

        return back()->with('status', __('Promosiya aktivləşdirildi.'));
    }

    public function cancel(int $id)
    {
        $this->requirePermission('update product');
        $this->service->cancel(ListingPromotion::query()->findOrFail($id));

        return back()->with('status', __('Promosiya ləğv edildi.'));
    }

    private function validatePackage(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:promoted,premium'],
            'days' => ['required', 'integer', 'min:1', 'max:365'],
            'price' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function packageData(array $data, Request $request): array
    {
        return [
            'name' => array_fill_keys(['az', 'en', 'ru', 'tr'], $data['name']),
            'type' => $data['type'],
            'days' => $data['days'],
            'price' => $data['price'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
