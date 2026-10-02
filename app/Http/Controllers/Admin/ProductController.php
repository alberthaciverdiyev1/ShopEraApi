<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Gender;
use App\Support\Features;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Banner\Entities\Banner;
use Modules\Brand\Entities\Brand;
use Modules\Category\Entities\Category;
use Modules\Filter\Entities\Filter;
use Modules\Filter\Entities\ProductFilter;
use Modules\Color\Entities\Color;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductImage;
use Modules\Product\Entities\ProductVideo;
use Modules\Size\Entities\Size;

class ProductController extends AdminController
{
    protected string $title = 'Məhsullar';

    private array $locales = ['az', 'en', 'ru', 'tr'];

    public function index(Request $request)
    {
        $this->requirePermission('view products');

        $rows = $this->query($request)->paginate(20)->withQueryString();

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

        $product = Product::query()
            ->with(['images', 'colors', 'sizes', 'category', 'brand', 'videos'])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews')
            ->findOrFail($id);

        return view('admin.pages.products.show', [
            'title' => admin_label($product, 'title', '#'.$product->id),
            'product' => $product,
        ]);
    }

    public function edit(int $id)
    {
        $this->requirePermission('update product');

        $product = Product::query()->with(['images', 'colors', 'sizes', 'videos', 'productFilters', 'category'])->findOrFail($id);
        $banner = Banner::query()->where('product_id', $product->id)->latest('id')->first();

        $currentMain = $product->category?->parent_id ?? $product->category_id;
        $currentChildren = $currentMain ? Category::query()->where('parent_id', $currentMain)->orderBy('id')->get() : collect();

        $filters = $product->category_id
            ? Filter::query()->whereHas('categories', fn ($q) => $q->where('categories.id', $product->category_id))->orderBy('id')->get()
            : collect();

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
            'locales' => $this->locales,
        ]);
    }

    public function create()
    {
        $this->requirePermission('add product');

        $oldCategory = old('category_id');
        $currentMain = old('main_category_id') ?: null;
        $currentChildren = $currentMain ? Category::query()->where('parent_id', $currentMain)->orderBy('id')->get() : collect();

        $filters = $oldCategory
            ? Filter::query()->whereHas('categories', fn ($q) => $q->where('categories.id', $oldCategory))->orderBy('id')->get()
            : collect();

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
            'locales' => $this->locales,
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePermission('add product');

        $limit = Features::limit('max_products');
        if ($limit !== null && $limit >= 0 && Product::query()->count() >= $limit) {
            return back()->withInput()->withErrors(['limit' => "Məhsul limitinə çatdınız ($limit). Planı yüksəldin."]);
        }

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

        $product = new Product();
        $product->fill([
            'user_id' => admin_user()->id,
            'price' => $data['price'],
            'discount' => $data['discount'] ?? null,
            'stock_count' => $data['stock_count'],
            'weight' => $data['weight'] ?? null,
            'sku' => ! empty($data['sku']) ? $data['sku'] : $this->nextSku(),
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

    private function nextSku(): string
    {
        $last = Product::query()->whereNotNull('sku')->orderByDesc('id')->value('sku');
        $next = ($last && preg_match('/P(\d+)/', $last, $m)) ? ((int) $m[1] + 1) : 1;
        do {
            $sku = 'P'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (Product::query()->where('sku', $sku)->exists());

        return $sku;
    }

    public function update(Request $request, int $id)
    {
        $this->requirePermission('update product');

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

        $product = Product::query()->with('images')->findOrFail($id);

        foreach ($product->images as $image) {
            if ($image->getRawOriginal('image_path') && ! Str::startsWith($image->getRawOriginal('image_path'), 'http')) {
                Storage::disk('public')->delete($image->getRawOriginal('image_path'));
            }
        }

        $product->colors()->detach();
        $product->sizes()->detach();
        $product->images()->delete();
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', __('Məhsul silindi.'));
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

    private function query(Request $request)
    {
        $query = Product::query()->with(['images', 'category', 'brand'])->latest('id');

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($inner) use ($term) {
                $inner->where('title->az', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
                if (is_numeric($term)) {
                    $inner->orWhere('id', (int) $term);
                }
            });
        }

        foreach (['category_id', 'brand_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->query($filter));
            }
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->query('approval_status'));
        }

        return $query;
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
