<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Gender;
use App\Support\Features;
use App\Support\PlanLimits;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Banner\Entities\Banner;
use Modules\Brand\Entities\Brand;
use Modules\Category\Entities\Category;
use Modules\CjDropShopping\Entities\DropshippingProductDetail;
use Modules\Color\Entities\Color;
use Modules\Filter\Entities\ProductFilter;
use Modules\Filter\Services\FilterService;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductImage;
use Modules\Product\Entities\ProductVideo;
use Modules\Product\Services\ProductService;
use Modules\Size\Entities\Size;

class ProductController extends AdminController
{
    protected string $title = 'Məhsullar';

    private function service(): ProductService
    {
        return app(ProductService::class);
    }

    public function index(Request $request)
    {
        $this->requirePermission('view products');

        $rows = $this->service()->adminQuery($request)->paginate(20)->withQueryString();

        if ($this->isHtmx($request)) {
            return view('admin.pages.products._table', ['rows' => $rows]);
        }

        return view('admin.pages.products.index', [
            'title' => $this->title,
            'rows' => $rows,
            'categories' => Category::query()->orderBy('id')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'approvalStatuses' => ['approved', 'pending', 'rejected'],
            'filters' => $request->only(['q', 'category_id', 'brand_id', 'is_active', 'approval_status']),
        ]);
    }

    public function show(int $id)
    {
        $this->requirePermission('view products');

        $product = $this->service()->adminFind($id);

        return view('admin.pages.products.show', [
            'title' => admin_label($product, 'title', '#'.$product->id),
            'product' => $product,
            'dropshipping' => DropshippingProductDetail::query()->where('product_id', $product->id)->first(),
        ]);
    }

    public function edit(int $id)
    {
        $this->requirePermission('update product');

        $product = $this->service()->adminFindForEdit($id);
        $banner = Banner::query()->where('product_id', $product->id)->latest('id')->first();

        $currentMain = $product->category?->parent_id ?? $product->category_id;
        $currentChildren = $currentMain ? Category::query()->where('parent_id', $currentMain)->orderBy('id')->get() : collect();

        $filters = app(FilterService::class)->forCategory($product->category_id);

        return view('admin.pages.products.edit', [
            'title' => 'Redaktə: '.admin_label($product, 'title', '#'.$product->id),
            'product' => $product,
            'bannerType' => $banner?->type,
            'mainCategories' => Category::query()->whereNull('parent_id')->orderBy('id')->get(),
            'currentMain' => $currentMain,
            'currentCategory' => $product->category_id,
            'currentChildren' => $currentChildren,
            'filters' => $filters,
            'productFilters' => $product->productFilters->pluck('value', 'filter_id')->all(),
            'categories' => Category::query()->orderBy('id')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'colors' => Color::query()->orderByDesc('sort_order')->get(),
            'sizes' => Size::query()->orderByDesc('sort_order')->get(),
            'genders' => Gender::cases(),
            'locales' => $this->enabledLocales(),
        ]);
    }

    public function create()
    {
        $this->requirePermission('add product');

        $oldCategory = old('category_id');
        $currentMain = old('main_category_id') ?: null;
        $currentChildren = $currentMain ? Category::query()->where('parent_id', $currentMain)->orderBy('id')->get() : collect();

        $filters = app(FilterService::class)->forCategory($oldCategory);

        return view('admin.pages.products.create', [
            'title' => 'Yeni məhsul',
            'bannerType' => null,
            'mainCategories' => Category::query()->whereNull('parent_id')->orderBy('id')->get(),
            'currentMain' => $currentMain,
            'currentCategory' => $oldCategory,
            'currentChildren' => $currentChildren,
            'filters' => $filters,
            'categories' => Category::query()->orderBy('id')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'colors' => Color::query()->orderByDesc('sort_order')->get(),
            'sizes' => Size::query()->orderByDesc('sort_order')->get(),
            'genders' => Gender::cases(),
            'locales' => $this->enabledLocales(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePermission('add product');

        if (PlanLimits::reached('max_products', 1)) {
            $limit = Features::limit('max_products');

            return back()->withInput()->withErrors(['limit' => "Məhsul limitinə çatdınız ($limit). Planı yüksəldin."]);
        }

        $this->guardStorage($request);

        $data = $request->validate([
            'title.az' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.tr' => ['nullable', 'string', 'max:255'],
            'description.az' => ['required', 'string'],
            'description.en' => ['nullable', 'string'],
            'description.ru' => ['nullable', 'string'],
            'description.tr' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'stock_count' => ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku'],
            'main_category_id' => ['nullable', 'exists:categories,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'gender' => ['nullable', 'string'],
            'purchase_limit' => ['nullable', 'integer', 'min:0'],
            'discount_expire_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_suggest' => ['nullable', 'boolean'],
            'approval_status' => ['nullable', 'in:approved,pending,rejected'],
            'colors' => ['nullable', 'array'],
            'size_ids' => ['nullable', 'array'],
            'size_ids.*' => ['integer', 'exists:sizes,id'],
            'sizes' => ['nullable', 'array'],
            'sizes.*.price' => ['nullable', 'numeric', 'min:0'],
            'sizes.*.discount' => ['nullable', 'numeric', 'min:0'],
            'new_images' => ['nullable', 'array'],
            'new_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['file', 'max:51200'],
            'filters' => ['nullable', 'array'],
            'banner_type' => ['nullable', 'in:big,middle,small'],
        ]);

        $product = new Product;
        $product->fill([
            'user_id' => admin_user()->id,
            'price' => $data['price'],
            'discount' => $data['discount'] ?? null,
            'stock_count' => $data['stock_count'],
            'weight' => $data['weight'] ?? null,
            'sku' => ! empty($data['sku']) ? $data['sku'] : $this->service()->nextSku(),
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'gender' => $data['gender'] ?? null,
            'purchase_limit' => $data['purchase_limit'] ?? null,
            'discount_expire_date' => $data['discount_expire_date'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'is_pinned' => $request->boolean('is_pinned'),
            'is_suggest' => $request->boolean('is_suggest'),
            'approval_status' => $data['approval_status'] ?? 'approved',
        ]);
        $product->title = array_filter($data['title'] ?? [], fn ($v) => $v !== null && $v !== '');
        $product->description = array_filter($data['description'] ?? [], fn ($v) => $v !== null && $v !== '');
        $product->save();

        $product->colors()->sync(array_map('intval', $request->input('colors', [])));
        $this->syncSizes($product, (array) $request->input('size_ids', []), (array) $request->input('sizes', []));
        $this->syncImages($product, $request);
        $this->syncVideos($product, $request);
        $this->syncFilters($product, $request);
        $this->syncBanner($product, $request->input('banner_type'));

        return redirect()->route('admin.products.show', $product->id)->with('status', __('Məhsul əlavə edildi.'));
    }

    public function update(Request $request, int $id)
    {
        $this->requirePermission('update product');

        $this->guardStorage($request);

        $product = Product::query()->with('sizes')->findOrFail($id);

        $data = $request->validate([
            'title.az' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.tr' => ['nullable', 'string', 'max:255'],
            'description.az' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'description.ru' => ['nullable', 'string'],
            'description.tr' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'stock_count' => ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:50'],
            'main_category_id' => ['nullable', 'exists:categories,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'gender' => ['nullable', 'string'],
            'purchase_limit' => ['nullable', 'integer', 'min:0'],
            'discount_expire_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_suggest' => ['nullable', 'boolean'],
            'approval_status' => ['nullable', 'in:approved,pending,rejected'],
            'colors' => ['nullable', 'array'],
            'size_ids' => ['nullable', 'array'],
            'size_ids.*' => ['integer', 'exists:sizes,id'],
            'sizes' => ['nullable', 'array'],
            'sizes.*.price' => ['nullable', 'numeric', 'min:0'],
            'sizes.*.discount' => ['nullable', 'numeric', 'min:0'],
            'new_images' => ['nullable', 'array'],
            'new_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer', 'exists:product_image,id'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['file', 'max:51200'],
            'delete_videos' => ['nullable', 'array'],
            'delete_videos.*' => ['integer', 'exists:product_videos,id'],
            'filters' => ['nullable', 'array'],
            'banner_type' => ['nullable', 'in:big,middle,small'],
        ]);

        $oldStock = (int) $product->stock_count;

        $product->fill([
            'price' => $data['price'],
            'discount' => $data['discount'] ?? null,
            'stock_count' => $data['stock_count'],
            'weight' => $data['weight'] ?? null,
            'sku' => $data['sku'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'gender' => $data['gender'] ?? null,
            'purchase_limit' => $data['purchase_limit'] ?? null,
            'discount_expire_date' => $data['discount_expire_date'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'is_pinned' => $request->boolean('is_pinned'),
            'is_suggest' => $request->boolean('is_suggest'),
            'approval_status' => $data['approval_status'] ?? $product->approval_status,
        ]);

        $product->title = array_merge($product->getTranslations('title'), array_filter($data['title'] ?? [], fn ($v) => $v !== null));
        $product->description = array_merge($product->getTranslations('description'), array_filter($data['description'] ?? [], fn ($v) => $v !== null));
        $product->save();

        $product->colors()->sync(array_map('intval', $request->input('colors', [])));

        $this->syncSizes($product, (array) $request->input('size_ids', []), (array) $request->input('sizes', []));
        $this->syncImages($product, $request);
        $this->syncVideos($product, $request);
        $this->syncFilters($product, $request);
        $this->syncBanner($product, $request->input('banner_type'));

        $message = 'Məhsul yeniləndi.';
        if ($oldStock <= 0 && (int) $product->stock_count > 0) {
            $message = 'Məhsul yeniləndi. Stok bərpa olundu — abunəçilərə bildiriş göndərilə bilər.';
        }

        return redirect()->route('admin.products.show', $product->id)->with('status', $message);
    }

    public function destroy(int $id)
    {
        $this->requirePermission('delete product');

        $this->service()->adminDelete($id);

        return redirect()->route('admin.products.index')->with('status', __('Məhsul silindi.'));
    }

    /** Deletes every selected product (checkbox column on the list). */
    public function bulkDestroy(Request $request)
    {
        $this->requirePermission('delete product');

        $ids = collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->service()->adminDelete($id);
                $deleted++;
            } catch (\Throwable) {
                // Skip a product that vanished or could not be removed.
            }
        }

        $message = $deleted > 0
            ? __(':count məhsul silindi.', ['count' => $deleted])
            : __('Heç bir məhsul seçilmədi.');

        if ($this->isHtmx($request)) {
            $rows = $this->service()->adminQuery($request)->paginate(20)->withQueryString();

            return response()
                ->view('admin.pages.products._table', ['rows' => $rows])
                ->header('HX-Trigger', $this->htmxTriggers([
                    'toast' => ['type' => $deleted > 0 ? 'success' : 'error', 'message' => $message],
                ]));
        }

        return redirect()->route('admin.products.index')->with('status', $message);
    }

    public function destroyImage(int $imageId)
    {
        $this->requirePermission('update product');

        $image = ProductImage::query()->findOrFail($imageId);
        $raw = $image->getRawOriginal('image_path');

        if ($raw && ! Str::startsWith($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $productId = $image->product_id;
        $image->delete();

        return back()->with('status', __('Şəkil silindi.'));
    }

    public function prices()
    {
        $this->requirePermission('update product');

        return view('admin.pages.products.prices', [
            'title' => 'Toplu qiymət dəyişikliyi',
            'categories' => Category::query()->orderBy('id')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
        ]);
    }

    public function applyPrices(Request $request)
    {
        $this->requirePermission('update product');

        $data = $request->validate([
            'type' => ['required', 'in:increment,decrement'],
            'mode' => ['required', 'in:percentage,amount'],
            'value' => ['required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
        ]);

        $query = Product::query();
        if (! empty($data['category_id'])) {
            $query->where('category_id', $data['category_id']);
        }
        if (! empty($data['brand_id'])) {
            $query->where('brand_id', $data['brand_id']);
        }

        $updated = 0;
        $query->orderBy('id')->chunkById(200, function ($products) use ($data, &$updated) {
            foreach ($products as $product) {
                $price = (float) $product->price;
                $delta = $data['mode'] === 'percentage' ? $price * ((float) $data['value'] / 100) : (float) $data['value'];
                $newPrice = $data['type'] === 'increment' ? $price + $delta : $price - $delta;
                $product->price = max(0, round($newPrice, 2));
                $product->save();
                $updated++;
            }
        });

        return redirect()->route('admin.products.prices')->with('status', __(':count məhsulun qiyməti yeniləndi.', ['count' => $updated]));
    }

    public function destroyVideo(int $id)
    {
        $this->requirePermission('update product');

        $video = ProductVideo::query()->findOrFail($id);
        $raw = $video->getRawOriginal('video_path');

        if ($raw && ! Str::startsWith($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $video->delete();

        return back()->with('status', __('Video silindi.'));
    }

    /** Reject the upload when it would push the tenant over its storage limit. */
    private function guardStorage(Request $request): void
    {
        $bytes = 0;

        foreach (['new_images', 'videos'] as $field) {
            foreach ((array) $request->file($field, []) as $file) {
                $bytes += (int) $file->getSize();
            }
        }

        if ($bytes > 0 && PlanLimits::storageFull($bytes)) {
            throw ValidationException::withMessages([
                'new_images' => __('Yaddaş limitinə çatdınız. Fayl yükləmək üçün planı yüksəldin.'),
            ]);
        }
    }

    private function syncVideos(Product $product, Request $request): void
    {
        $delete = array_map('intval', $request->input('delete_videos', []));

        if (! empty($delete)) {
            $product->videos()->whereIn('id', $delete)->get()
                ->each(function (ProductVideo $video) {
                    $raw = $video->getRawOriginal('video_path');
                    if ($raw && ! Str::startsWith($raw, 'http')) {
                        Storage::disk('public')->delete($raw);
                    }
                    $video->delete();
                });
        }

        foreach ($request->file('videos', []) as $file) {
            $path = $file->store(TenantContext::storagePath('videos'), 'public');
            $product->videos()->create(['video_path' => $path]);
        }
    }

    private function syncFilters(Product $product, Request $request): void
    {
        ProductFilter::query()->where('product_id', $product->id)->delete();

        foreach ((array) $request->input('filters', []) as $filterId => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            ProductFilter::query()->create([
                'product_id' => $product->id,
                'filter_id' => (int) $filterId,
                'value' => (string) $value,
            ]);
        }
    }

    /**
     * Creates or updates an active banner for this product using its own
     * photos: the first image becomes the banner, the second (if any) the
     * secondary image. Storefront reads it via /api/banner?type=...
     */
    private function syncBanner(Product $product, ?string $type): void
    {
        if (empty($type)) {
            return;
        }

        $images = $product->images()->orderBy('id')->take(2)->get();
        $first = $images->first();

        if (! $first) {
            return;
        }

        Banner::query()->updateOrCreate(
            ['product_id' => $product->id],
            [
                'image' => $first->getRawOriginal('image_path'),
                'second_image' => $images->get(1)?->getRawOriginal('image_path'),
                'type' => $type,
                'url' => null,
                'is_active' => true,
            ]
        );
    }

    /**
     * Each selected size carries its own price (and optional discount)
     * on the pivot. A blank field falls back to the product's own value.
     */
    private function syncSizes(Product $product, array $sizeIds, array $prices = []): void
    {
        $existing = $product->sizes()->get()->keyBy('id');
        $sync = [];

        foreach ($sizeIds as $sizeId) {
            $sizeId = (int) $sizeId;
            $row = (array) ($prices[$sizeId] ?? []);
            $pivot = $existing->get($sizeId)?->pivot;

            $pick = function (string $field) use ($row, $pivot, $product) {
                $value = $row[$field] ?? null;

                if ($value !== null && $value !== '') {
                    return $value;
                }

                return $pivot->{$field} ?? $product->{$field};
            };

            $sync[$sizeId] = [
                'price' => $pick('price'),
                'discount' => $pick('discount'),
            ];
        }

        $product->sizes()->sync($sync);
    }

    private function syncImages(Product $product, Request $request): void
    {
        $delete = array_map('intval', $request->input('delete_images', []));

        if (! empty($delete)) {
            $product->images()->whereIn('id', $delete)->get()
                ->each(function (ProductImage $image) {
                    $raw = $image->getRawOriginal('image_path');
                    if ($raw && ! Str::startsWith($raw, 'http')) {
                        Storage::disk('public')->delete($raw);
                    }
                    $image->delete();
                });
        }

        foreach ($request->file('new_images', []) as $file) {
            $path = $file->store(TenantContext::storagePath('products'), 'public');
            $product->images()->create([
                'image_path' => $path,
                'color_id' => null,
            ]);
        }
    }
}
