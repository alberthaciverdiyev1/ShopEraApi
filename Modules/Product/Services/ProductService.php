<?php

namespace Modules\Product\Services;

use App\Enums\Gender;
use App\Helpers\TranslateHelper as Translate;
use App\Support\DbExtensions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Brand\Entities\Brand;
use Modules\Brand\Http\Transformers\BrandResource;
use Modules\Category\Entities\Category;
use Modules\Color\Entities\Color;
use Modules\Color\Http\Transformers\ColorResource;
use Modules\Order\Entities\OrderItem;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductImage;
use Modules\Product\Entities\ProductVideo;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Http\Resources\ProductStoryVideoResource;
use Modules\Size\Entities\Size;
use Modules\Size\Http\Transformers\SizeResource;
use Modules\User\Entities\Basket;

class ProductService
{
    private Product $model;

    private ProductSubscribeService $subscribe_service;

    public function __construct(Product $model, ProductSubscribeService $subscribe_service)
    {
        $this->model = $model;
        $this->subscribe_service = $subscribe_service;
    }

    public function list($request): JsonResponse
    {
        $params = $request->all();
        $locale = app()->getLocale();

        $discountCount = Product::where('discount', '>', 0)
            ->whereColumn('discount', '<', 'price')
            ->publiclyAvailable()
            ->count();

        $query = Product::query()
            ->with(['colors', 'sizes', 'images', 'videos', 'category', 'brand', 'city'])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews');

        $isAdminRequest = ! empty($params['is_admin']) && auth('sanctum')->check();

        if ($isAdminRequest && isset($params['is_active'])) {
            $query->where('is_active', $params['is_active']);
        } elseif ($isAdminRequest) {
            $query->where('is_active', 1);
        } else {
            $query->publiclyAvailable();
        }

        if ($isAdminRequest && ! empty($params['approval_status'])) {
            $query->where('approval_status', $params['approval_status']);
        }

        if (isset($params['is_suggest'])) {
            $query->where('is_suggest', $params['is_suggest']);
        }

        if (! empty($params['discount'])) {
            // `discount` is the sale price; a product is on sale only when it is
            // set and actually below the regular price.
            $query->where('discount', '>', 0)
                ->whereColumn('discount', '<', 'price');
        }

        if (! empty($params['gender']) && in_array($params['gender'], ['male', 'female', 'kids'])) {
            $query->where('gender', Gender::fromString($params['gender'])->value);
        }

        rangeFilter($query, 'price', $params);

        if (! empty($params['category_ids']) && is_array($params['category_ids'])) {

            $categoryIds = collect($params['category_ids']);
            $allCategoryIds = collect();

            $fetchChildren = function ($ids) use (&$fetchChildren, &$allCategoryIds) {
                $children = Category::whereIn('parent_id', $ids)->pluck('id');
                if ($children->isNotEmpty()) {
                    $allCategoryIds = $allCategoryIds->merge($children);
                    $fetchChildren($children);
                }
            };

            $allCategoryIds = $allCategoryIds->merge($categoryIds);

            $fetchChildren($categoryIds);

            $finalIds = $allCategoryIds->unique()->values();

            $query->whereIn('category_id', $finalIds);
        }

        if (! empty($params['brand_ids']) && is_array($params['brand_ids'])) {
            $query->whereIn('brand_id', $params['brand_ids']);
        }

        // Marketplace listing filters: location, condition, delivery.
        if (! empty($params['city_ids']) && is_array($params['city_ids'])) {
            $query->whereIn('city_id', $params['city_ids']);
        } elseif (! empty($params['city_id'])) {
            $query->where('city_id', $params['city_id']);
        }

        if (! empty($params['vendor_id'])) {
            $query->where('vendor_id', $params['vendor_id']);
        }

        if (! empty($params['condition']) && in_array($params['condition'], ['new', 'used'], true)) {
            $query->where('condition', $params['condition']);
        }

        if (! empty($params['has_delivery'])) {
            $query->where('has_delivery', true);
        }

        if (! empty($params['color_ids']) && is_array($params['color_ids'])) {
            $query->whereHas('colors', fn ($q) => $q->whereIn('colors.id', $params['color_ids']));
        }

        if (! empty($params['size_ids']) && is_array($params['size_ids'])) {
            $query->whereHas('sizes', fn ($q) => $q->whereIn('sizes.id', $params['size_ids']));
        }

        // Dynamic filters: ?filters[<filter_id>][]=value (OR inside a filter,
        // AND across different filters).
        if (! empty($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $filterId => $values) {
                $values = array_values(array_filter((array) $values, fn ($value) => $value !== '' && $value !== null));

                if (empty($values)) {
                    continue;
                }

                $query->whereHas('productFilters', function ($q) use ($filterId, $values) {
                    $q->where('filter_id', $filterId)->whereIn('value', $values);
                });
            }
        }

        // Dependent filter tree: ?filter_value_ids[]=… matches products that
        // hold every one of the selected values (one per filter in the chain).
        if (! empty($params['filter_value_ids']) && is_array($params['filter_value_ids'])) {
            $valueIds = array_values(array_filter(array_map('intval', $params['filter_value_ids'])));

            if ($valueIds !== []) {
                $query->whereHas(
                    'filterValues',
                    fn ($q) => $q->whereIn('filter_value_id', $valueIds),
                    '=',
                    count($valueIds)
                );
            }
        }

        if (! empty($params['search'])) {
            filterLike($query, ['title', 'description', 'sku'], $params);
        }

        if ($request->hasFile('image')) {
            filterByImage($query, $request->file('image'));
        }

        $isFiltered = collect([
            $params['category_ids'] ?? null,
            $params['brand_ids'] ?? null,
            $params['color_ids'] ?? null,
            $params['size_ids'] ?? null,
            $params['search'] ?? null,
            $params['gender'] ?? null,
            $params['discount'] ?? null,
            $params['order_type'] ?? null,
            $params['order_by'] ?? null,
            $params['search'] ?? null,
        ])->filter()->isNotEmpty();

        // The house catalogue is favoured in *search* only, where the shopper is
        // looking for a specific thing and equally matching results have to be
        // ordered somehow. Plain browsing keeps its normal order, and an explicit
        // sort the shopper chose is never overridden.
        $prioritiseOwnProducts = ! empty($params['search']) && empty($params['order_by']);

        // Pinning was storable and readable but never sorted on, so marking a
        // product pinned in the panel changed nothing a shopper could see. It
        // is an editorial choice, so it outranks the random order plain
        // browsing uses; only a sort the shopper picked themselves wins over
        // it, which is the same rule the house catalogue follows above.
        $pinnedFirst = empty($params['order_by']);

        if (! empty($params['is_admin'])) {
            orderBy($query, $params);
        } elseif (! empty($params['featured'])) {
            // Home page ("əsas səhifə"): only Premium stays pinned to the front
            // for the whole duration of its placement, then the usual order.
            $query->orderByRaw(Product::HOME_PRIORITY_SQL.' desc');
            orderBy($query, $params);
        } elseif (! empty($params['marketplace']) || ! empty($params['search'])) {
            // Marketplace feed and search: paid placements always float above
            // free listings (Premium > VIP > İrəli çək). Without a sort the
            // shopper picked, listings of the same tier rotate randomly so every
            // paid ad gets equal exposure — Premium/VIP within their category.
            $query->orderByRaw(Product::PLACEMENT_PRIORITY_SQL.' desc');

            if (empty($params['order_by'])) {
                $query->orderByRaw('random()');
            } else {
                orderBy($query, $params);
            }
        } elseif (! $isFiltered) {
            if ($pinnedFirst) {
                $query->orderByDesc('is_pinned');
            }

            $query->inRandomOrder();
        } else {
            if ($pinnedFirst) {
                $query->orderByDesc('is_pinned');
            }

            orderBy($query, $params);
        }

        // Tətbiq yalnız ana səhifədəki yan-yana lentlər üçün daha böyük
        // səhifə istəyir. Soruşmayan build-lər üçün ölçü olduğu kimi 20
        // qalır, yəni mağazadakı versiyaların cavabı dəyişmir.
        $perPage = $isAdminRequest
            ? min(max((int) $request->input('per_page', 21), 1), 100)
            : min(max((int) $request->input('per_page', 20), 1), 50);

        $data = $query->paginate($perPage);

        $data->getCollection()->transform(function ($product) {
            $product->rate = ($product->reviews_avg_rate !== null) ? round($product->reviews_avg_rate, 2) : 0;
            $product->rate_count = $product->reviews_count;

            $user = auth('sanctum')->user();

            $product->is_favorite = $user ? $product->favoritedBy()->where('user_id', $user->id)->exists() : false;

            $product->is_subscribe = $user ? $product->subscribedBy()->where('user_id', $user->id)->exists() : false;

            return $product;
        });

        return response()->json(isset($params['is_application']) ? ProductResource::collection($data) : [
            'success' => 200,
            'message' => __('Products retrieved successfully.'),
            'data' => ProductResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'discount_count' => $discountCount,

            ],
        ]);
    }

    /**
     * Filter facets for the storefront: the brands, colours, sizes and price
     * range that actually exist among the products — optionally restricted to
     * a category and its children. Each entry carries how many products use it.
     */
    public function filters($request): JsonResponse
    {
        $params = $request->all();
        $categoryIds = $params['category_ids'] ?? null;

        $build = function () use ($categoryIds) {
            $query = Product::query()->publiclyAvailable();

            if (! empty($categoryIds) && is_array($categoryIds)) {
                $query->whereIn('category_id', $this->categoryWithDescendants($categoryIds));
            }

            return $query;
        };

        $brandCounts = $build()
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id as id, COUNT(*) as total')
            ->groupBy('brand_id')
            ->pluck('total', 'id');

        $brands = Brand::query()
            ->whereIn('id', $brandCounts->keys())
            ->orderBy('name')
            ->get()
            ->map(fn ($brand) => array_merge(
                (new BrandResource($brand))->resolve(),
                ['products_count' => (int) ($brandCounts[$brand->id] ?? 0)]
            ));

        $colorCounts = $build()
            ->join('color_product', 'color_product.product_id', '=', 'products.id')
            ->selectRaw('color_product.color_id as id, COUNT(DISTINCT products.id) as total')
            ->groupBy('color_product.color_id')
            ->pluck('total', 'id');

        $colors = Color::query()
            ->whereIn('id', $colorCounts->keys())
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($color) => array_merge(
                (new ColorResource($color))->resolve(),
                ['products_count' => (int) ($colorCounts[$color->id] ?? 0)]
            ));

        $sizeCounts = $build()
            ->join('product_size', 'product_size.product_id', '=', 'products.id')
            ->selectRaw('product_size.size_id as id, COUNT(DISTINCT products.id) as total')
            ->groupBy('product_size.size_id')
            ->pluck('total', 'id');

        $sizes = Size::query()
            ->whereIn('id', $sizeCounts->keys())
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($size) => array_merge(
                (new SizeResource($size))->resolve(),
                ['products_count' => (int) ($sizeCounts[$size->id] ?? 0)]
            ));

        $price = $build()->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        return responseHelper(__('Product filters retrieved successfully.'), 200, [
            'brands' => $brands,
            'colors' => $colors,
            'sizes' => $sizes,
            'price' => [
                'min' => (float) ($price->min_price ?? 0),
                'max' => (float) ($price->max_price ?? 0),
            ],
            'total' => $build()->count(),
        ]);
    }

    /**
     * A category plus every descendant category id.
     */
    private function categoryWithDescendants(array $ids): array
    {
        $all = collect($ids);

        $fetchChildren = function ($parentIds) use (&$fetchChildren, &$all) {
            $children = Category::whereIn('parent_id', $parentIds)->pluck('id');
            if ($children->isNotEmpty()) {
                $all = $all->merge($children);
                $fetchChildren($children);
            }
        };

        $fetchChildren($ids);

        return $all->unique()->values()->all();
    }

    /**
     * Product details
     */
    public function detailsBySlug(string $slug): JsonResponse
    {
        $id = $this->model->newQuery()->where('slug', $slug)->value('id');

        if (! $id) {
            return responseHelper(__('Product not found.'), 404, []);
        }

        return $this->details((int) $id);
    }

    public function details(int $id): JsonResponse
    {
        try {
            $product = $this->model->with([
                'colors', 'sizes', 'images', 'videos', 'category.parent.parent.parent.parent', 'brand', 'city', 'reviews.user', 'user', 'vendor',
                'productFilters.filter', 'filterValues.filter', 'filterValues.value',
            ])->publiclyAvailable()->findOrFail($id);

            $product?->increment('views');

            $averageRate = $product->reviews()->avg('rate') ?? 5;

            $isFavorite = false;
            if (Auth::check()) {
                $isFavorite = $product->favoritedBy()
                    ->where('user_id', Auth::id())
                    ->exists();
            }
            $data = ProductResource::make($product);
            $data->rate = round($averageRate, 2);
            $data->rate_count = count($product->reviews);

            $data->is_favorite = $isFavorite;

            return responseHelper(__('Product details retrieved successfully.'), 200, $data);

        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Product not found.'), 403, []);
        }
    }

    public function storyVideos(): JsonResponse
    {
        $videos = ProductVideo::query()
            ->with([
                'product' => fn ($query) => $query
                    ->publiclyAvailable()
                    ->with(['images']),
            ])
            ->where('is_story_hidden', false)
            ->where(function ($query) {
                $query
                    ->where('story_expires_at', '>=', now())
                    ->orWhere(function ($query) {
                        $query
                            ->whereNull('story_expires_at')
                            ->where('created_at', '>=', now()->subDay());
                    });
            })
            ->whereHas('product', fn ($query) => $query->publiclyAvailable())
            ->latest()
            ->get();

        return responseHelper(__('Product story videos retrieved successfully.'),
            200,
            ProductStoryVideoResource::collection($videos)
        );
    }

    public function storyVideosAdmin($request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $isNumericSearch = ctype_digit($search);
        $locale = app()->getLocale();

        $limit = (int) $request->query('limit', 0);

        // unaccent() is optional; fall back to a plain ILIKE without it.
        $unaccent = DbExtensions::hasUnaccent();
        $titleLocaleRaw = $unaccent ? 'unaccent(title->>?) ILIKE unaccent(?)' : 'title->>? ILIKE ?';
        $titleAzRaw = $unaccent ? "unaccent(title->>'az') ILIKE unaccent(?)" : "title->>'az' ILIKE ?";

        $videos = ProductVideo::query()
            ->with(['product.images'])
            ->when($search !== '', function ($query) use ($search, $isNumericSearch, $locale, $titleLocaleRaw, $titleAzRaw) {
                $query->where(function ($query) use ($search, $isNumericSearch, $locale, $titleLocaleRaw, $titleAzRaw) {
                    if ($isNumericSearch) {
                        $query
                            ->where('id', (int) $search)
                            ->orWhere('product_id', (int) $search)
                            ->orWhere('video_path', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($productQuery) use ($search, $locale, $titleLocaleRaw, $titleAzRaw) {
                                $productQuery
                                    ->whereRaw($titleLocaleRaw, [$locale, "%{$search}%"])
                                    ->orWhereRaw($titleAzRaw, ["%{$search}%"]);
                            });
                    } else {
                        $query
                            ->where('video_path', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($productQuery) use ($search, $locale, $titleLocaleRaw, $titleAzRaw) {
                                $productQuery
                                    ->whereRaw($titleLocaleRaw, [$locale, "%{$search}%"])
                                    ->orWhereRaw($titleAzRaw, ["%{$search}%"]);
                            });
                    }
                });
            })
            ->when($limit > 0, fn ($query) => $query->limit($limit))
            ->latest()
            ->get();

        return responseHelper(__('Product story videos retrieved successfully.'),
            200,
            ProductStoryVideoResource::collection($videos)
        );
    }

    public function activateStoryVideo(int $id): JsonResponse
    {
        $video = ProductVideo::with(['product.images'])->findOrFail($id);

        $video->update([
            'is_story_hidden' => false,
            'story_expires_at' => now()->addDay(),
        ]);

        return responseHelper(__('Story video activated successfully.'),
            200,
            ProductStoryVideoResource::make($video->fresh(['product.images']))
        );
    }

    public function deactivateStoryVideo(int $id): JsonResponse
    {
        $video = ProductVideo::with(['product.images'])->findOrFail($id);

        $video->update([
            'is_story_hidden' => true,
            'story_expires_at' => null,
        ]);

        return responseHelper(__('Story video deactivated successfully.'),
            200,
            ProductStoryVideoResource::make($video->fresh(['product.images']))
        );
    }

    public function detailsAdmin(int $id): JsonResponse
    {
        try {
            $product = $this->model->withTrashed()->with([
                'colors', 'sizes', 'images', 'videos', 'category', 'brand', 'reviews.user',
            ])->findOrFail($id);

            $averageRate = $product->reviews()->avg('rate') ?? 5;
            $data = ProductResource::make($product);
            $data->rate = round($averageRate, 2);

            return response()->json([
                'success' => 200,
                'message' => __('Product details retrieved successfully.'),
                'data' => $product,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Product not found.'),
                'data' => [],
            ]);
        }
    }

    /**
     * Add product
     */
    //    public function add($request): JsonResponse
    //    {
    //        $data = $request->validated();
    //        if (!isset($data['sku'])) {
    //            $lastProduct = $this->model->orderByDesc('id')->first();
    //            if ($lastProduct && preg_match('/P(\d+)/', $lastProduct->sku, $matches)) {
    //                $nextNumber = (int)$matches[1] + 1;
    //            } else {
    //                $nextNumber = 1;
    //            }
    //
    //            $data['sku'] = 'P' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    //
    //            while ($this->model->where('sku', $data['sku'])->exists()) {
    //                $nextNumber++;
    //                $data['sku'] = 'P' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    //            }
    //        }
    //        if (isset($data['gender'])) {
    //            $data['gender'] = Gender::fromString($data['gender'])->value;
    //        }
    //
    //        $product = handleTransaction(function () use ($data, $request) {
    //            $images_arr = $request->hasFile('images') ? $request->file('images') : [];
    //            $videos_arr = $request->hasFile('videos') ? $request->file('videos') : [];
    //            $sizesData = $data['sizes'] ?? [];
    //            $imagesData = $request->input('images', []);
    //            $imageFiles = $request->file('images', []);
    //
    //
    //            $languages = ['az', 'ru', 'en', 'tr'];
    //
    //            $title = $data['title'] ?? ['az' => ''];
    //            foreach ($languages as $lang) {
    //                if (empty($title[$lang])) {
    //                    $title[$lang] = Translate::translate($title['az'], $lang);
    //                }
    //                $title[$lang] = Str::lower($title[$lang]);
    //            }
    //
    //            $description = $data['description'] ?? ['az' => ''];
    //            foreach ($languages as $lang) {
    //                if (empty($description[$lang])) {
    //                    $description[$lang] = Translate::translate($description['az'], $lang);
    //                }
    //                $description[$lang] = Str::lower($description[$lang]);
    //            }
    //
    //            $translations = [
    //                'title' => $title,
    //                'description' => $description,
    //            ];
    //            $colors = isset($data['colors']) && is_array($data['colors']) ? $data['colors'] : [];
    //            $sizes = isset($data['sizes']) && is_array($data['sizes']) ? $data['sizes'] : [];
    //
    //            unset($data['title'], $data['description'], $data['images'], $data['colors'], $data['sizes'], $data['videos']);
    //
    //            $product = $this->model->create($data);
    //
    //            $product->update($translations);
    //
    //            if (!empty($colors)) {
    //                $product->colors()->sync($colors);
    //            }
    //
    //            if (!empty($sizesData)) {
    //                $syncSizes = [];
    //                foreach ($sizesData as $item) {
    //                    $syncSizes[$item['size_id']] = [
    //                        'price' => $item['price'] ?? null,
    //                        'wholesale_price' => $item['wholesale_price'] ?? null,
    //                        'discount' => $item['discount'] ?? null
    //                    ];
    //                }
    //                $product->sizes()->sync($syncSizes);
    //            }
    //
    //            if (!empty($imageFiles)) {
    //                foreach ($imageFiles as $index => $imageFile) {
    //                    $url = compressAndUploadImage($imageFile['file'], 'products', 'product');
    //                    $relativePath = ltrim(str_replace(url('/'), '', $url), '/');
    //                    $product->images()->create([
    //                        'image_path' => $relativePath,
    //                        'color_id'   => $imagesData[$index]['color_id'] ?? null,
    //                        'embedding'  => generateImageEmbedding($imageFile['file']->getRealPath()),
    //                    ]);
    //                }
    //            }
    //
    //
    //            if (!empty($videos_arr) && is_array($videos_arr)) {
    //                $videos = [];
    //                foreach ($videos_arr as $video) {
    //                    $url = compressAndUploadVideo($video, 'videos', 'video');
    //                    $relativePath = str_replace(url('/'), '', $url);
    //
    //                    $videos[] = ['video_path' => ltrim($relativePath, '/')];
    //                }
    //                $product->videos()->createMany($videos);
    //            }
    //
    //            return $product->refresh();
    //        }, 'Product added successfully.', ProductResource::class);
    //
    //        return $product;
    //    }

    public function add($request, array $overrides = []): JsonResponse
    {
        $lock = Cache::lock('add_product_'.auth()->id(), 10);
        $productHash = '';

        if (! $lock->get()) {
            return responseHelper('The process is in progress, please wait.', 429);
        }
        try {
            $data = array_merge($request->validated(), $overrides);

            $productHash = md5($data['title']['az'].($data['category_id'] ?? ''));

            if (Cache::has('processing_product_'.$productHash)) {
                return responseHelper('This product is already being registered.', 429);
            }

            Cache::put('processing_product_'.$productHash, true, 5);

            if (! isset($data['sku'])) {
                $lastProduct = $this->model->orderByDesc('id')->first();
                if ($lastProduct && preg_match('/P(\d+)/', $lastProduct->sku, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                } else {
                    $nextNumber = 1;
                }

                $data['sku'] = 'P'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

                while ($this->model->where('sku', $data['sku'])->exists()) {
                    $nextNumber++;
                    $data['sku'] = 'P'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
                }
            }
            if (isset($data['gender'])) {
                $data['gender'] = Gender::fromString($data['gender'])->value;
            }

            $product = handleTransaction(function () use ($data, $request) {
                $images_arr = $request->hasFile('images') ? $request->file('images') : [];
                $videos_arr = $request->hasFile('videos') ? $request->file('videos') : [];
                $sizesData = $data['sizes'] ?? [];
                $imagesData = $request->input('images', []);
                $imageFiles = $request->file('images', []);

                $languages = ['az', 'ru', 'en', 'tr'];

                $title = $data['title'] ?? ['az' => ''];
                foreach ($languages as $lang) {
                    if (empty($title[$lang])) {
                        $title[$lang] = Translate::translate($title['az'], $lang);
                    }
                    $title[$lang] = Str::lower($title[$lang]);
                }

                $description = $data['description'] ?? ['az' => ''];
                foreach ($languages as $lang) {
                    if (empty($description[$lang])) {
                        $description[$lang] = Translate::translate($description['az'], $lang);
                    }
                    $description[$lang] = Str::lower($description[$lang]);
                }

                $translations = [
                    'title' => $title,
                    'description' => $description,
                ];
                $colors = isset($data['colors']) && is_array($data['colors']) ? $data['colors'] : [];
                $sizes = isset($data['sizes']) && is_array($data['sizes']) ? $data['sizes'] : [];

                unset($data['title'], $data['description'], $data['images'], $data['colors'], $data['sizes'], $data['videos']);

                $product = $this->model->create($data);

                $product->update($translations);

                if (! empty($colors)) {
                    $product->colors()->sync($colors);
                }

                if (! empty($sizesData)) {
                    $syncSizes = [];
                    foreach ($sizesData as $item) {
                        $syncSizes[$item['size_id']] = [
                            'price' => $item['price'] ?? null,
                            'wholesale_price' => $item['wholesale_price'] ?? null,
                            'discount' => $item['discount'] ?? null,
                        ];
                    }
                    $product->sizes()->sync($syncSizes);
                }

                if (! empty($imageFiles)) {
                    foreach ($imageFiles as $index => $imageFile) {
                        $url = compressAndUploadImage($imageFile['file'], 'products', 'product');
                        $relativePath = ltrim(str_replace(url('/'), '', $url), '/');
                        $product->images()->create([
                            'image_path' => $relativePath,
                            'color_id' => $imagesData[$index]['color_id'] ?? null,
                            'embedding' => generateImageEmbedding($imageFile['file']->getRealPath()),
                        ]);
                    }
                }

                if (! empty($videos_arr) && is_array($videos_arr)) {
                    $videos = [];
                    foreach ($videos_arr as $video) {
                        $url = compressAndUploadVideo($video, 'videos', 'video');
                        $relativePath = str_replace(url('/'), '', $url);

                        $videos[] = ['video_path' => ltrim($relativePath, '/')];
                    }
                    $product->videos()->createMany($videos);
                }

                return $product->refresh();
            }, 'Product added successfully.', ProductResource::class);

            return $product;

        } finally {
            $lock->release();
            Cache::forget('processing_product_'.$productHash);
        }
    }

    /**
     * Update product
     */
    public function update($request, int $id, array $overrides = [])
    {
        $data = array_merge($request->validated(), $overrides);

        $colors = $data['colors'] ?? [];
        $sizesData = $data['sizes'] ?? [];
        $titles = $data['title'] ?? [];
        $descriptions = $data['description'] ?? [];

        unset(
            $data['title'],
            $data['description'],
            $data['colors'],
            $data['sizes'],
            $data['images'],
            $data['videos'],
            $data['existing_images'],
            $data['existing_videos']
        );

        if (isset($data['gender'])) {
            $data['gender'] = Gender::fromString($data['gender'])->value;
        }

        return handleTransaction(function () use ($data, $titles, $descriptions, $colors, $sizesData, $request, $id) {
            $product = $this->model->findOrFail($id);
            $old_stock_count = $product->getOriginal('stock_count');

            $imageFiles = $request->file('images', []);
            $videoFiles = $request->file('videos', []);
            $cdnBaseUrl = rtrim(config('filesystems.disks.bunnycdn.pull_zone'), '/');

            $product->update($data);

            $languages = ['az', 'ru', 'en', 'tr'];
            $finalTitles = [];
            $finalDescriptions = [];

            foreach ($languages as $lang) {
                if (isset($titles[$lang])) {
                    $finalTitles[$lang] = Str::lower($titles[$lang]);
                }
                if (isset($descriptions[$lang])) {
                    $finalDescriptions[$lang] = Str::lower($descriptions[$lang]);
                }
            }

            // $product->title returns the *current locale's* string, not the
            // translation map, so decoding it yielded null and every update
            // silently dropped the languages the request did not carry — an
            // update with no title at all emptied it completely.
            $product->update([
                'title' => array_merge($product->getTranslations('title'), $finalTitles),
                'description' => array_merge(
                    $product->getTranslations('description'),
                    $finalDescriptions
                ),
            ]);

            // `colors_synced` lets a client say it sent its whole list even when
            // that list is empty; multipart has no way to express an empty array.
            $syncColors = $request->has('colors') || $request->boolean('colors_synced');

            if ($syncColors) {
                $product->colors()->sync($colors);
            }

            // The colours this product sells once this request is done. Computed
            // outside the block above because the image writes further down have
            // to be measured against it whether or not colours were part of the
            // payload.
            //
            // Cast rather than trust: the `colors.*` element rule is commented
            // out upstream, so these arrive unvalidated and a non-numeric one
            // would make Postgres reject the whole update.
            $colorIds = $syncColors
                ? array_values(array_filter(array_map('intval', (array) $colors)))
                : $product->colors()->pluck('colors.id')->map(fn ($id) => (int) $id)->all();

            // An image may only claim a colour the product still sells. The
            // shopper app filters the gallery on this id, so a stale one makes
            // the photo vanish from every colour instead of appearing under
            // one; null is the right fallback, which shows it under all.
            //
            // Applied at each write below rather than once here: an earlier
            // attempt cleaned the table right after the sync, and the
            // existing_images loop thirty lines later wrote the client's stale
            // value straight back over it. Both clients resend the old tag, so
            // the cleanup was inert.
            $keepColor = fn ($value) => $value !== null && in_array((int) $value, $colorIds, true)
                ? (int) $value
                : null;

            // Images the payload never mentions still have to be corrected.
            ProductImage::where('product_id', $product->id)
                ->whereNotNull('color_id')
                ->when($colorIds !== [], fn ($query) => $query->whereNotIn('color_id', $colorIds))
                ->update(['color_id' => null]);

            if ($request->has('sizes')) {
                $syncSizes = [];
                foreach ($sizesData as $item) {
                    if (isset($item['size_id'])) {
                        $syncSizes[$item['size_id']] = [
                            'price' => $item['price'] ?? null,
                            'wholesale_price' => $item['wholesale_price'] ?? null,
                            'discount' => $item['discount'] ?? null,
                        ];
                    }
                }
                $product->sizes()->sync($syncSizes);
            }

            $existingImages = $request->input('existing_images', []);
            $existingImageIds = collect($existingImages)->pluck('id')->filter()->toArray();

            // Same reasoning as colours: removing every photo sends no key.
            if ($request->has('existing_images') || $request->boolean('images_synced')) {
                $imagesToDelete = $product->images()
                    ->when(! empty($existingImageIds), fn ($q) => $q->whereNotIn('id', $existingImageIds))
                    ->get();

                foreach ($imagesToDelete as $img) {
                    if (Storage::disk('bunnycdn')->exists($img->image_path)) {
                        Storage::disk('bunnycdn')->delete($img->image_path);
                    }
                    $img->delete();
                }

                foreach ($existingImages as $existingItem) {
                    if (isset($existingItem['id'])) {
                        $product->images()->where('id', $existingItem['id'])->update([
                            'color_id' => $keepColor($existingItem['color_id'] ?? null),
                        ]);
                    }
                }
            }

            if (! empty($imageFiles)) {
                foreach ($imageFiles as $index => $imageArray) {
                    $file = is_array($imageArray) ? ($imageArray['file'] ?? null) : $imageArray;
                    if (! $file) {
                        continue;
                    }
                    $fullUrl = compressAndUploadImage($file, 'products', 'product');
                    $product->images()->create([
                        'image_path' => $fullUrl,
                        'color_id' => $keepColor($request->input("images.{$index}.color_id")),
                        'embedding' => generateImageEmbedding($file->getRealPath()),
                    ]);
                }
            }

            $existingVideoIds = $request->input('existing_videos', []);
            if ($request->has('existing_videos')) {
                $videosToDelete = $product->videos()
                    ->when(! empty($existingVideoIds), fn ($q) => $q->whereNotIn('id', $existingVideoIds))
                    ->get();

                foreach ($videosToDelete as $video) {
                    $videoPath = ltrim(str_replace($cdnBaseUrl, '', $video->video_path), '/');
                    if (Storage::disk('bunnycdn')->exists($videoPath)) {
                        Storage::disk('bunnycdn')->delete($videoPath);
                    }
                    $video->delete();
                }
            }

            if (! empty($videoFiles)) {
                foreach ($videoFiles as $videoFile) {
                    $cdnUrl = compressAndUploadVideo($videoFile, 'videos', 'Video');
                    $product->videos()->create(['video_path' => $cdnUrl]);
                }
            }

            if ($old_stock_count <= 0 && $product->stock_count > 0) {
                $this->subscribe_service->notifySubscribers($product);
            }

            return $product->refresh();
        }, 'Product updated successfully.', ProductResource::class);
    }

    /**
     * Delete product
     */
    public function delete(int $id): JsonResponse
    {
        $response = handleTransaction(
            function () use ($id) {
                $product = $this->model->findOrFail($id);

                Basket::where('product_id', $product->id)->delete();

                $product->delete();

                return $product;
            },
            'Product deleted successfully.'
        );

        return $response;
    }

    public function statistics(): JsonResponse
    {
        $withRelations = ['images', 'brand'];

        $statistics = [
            'total_products' => $this->model->count(),
            'discounted_products' => $this->model->whereNotNull('discount')->count(),

            'most_viewed_products' => ProductResource::collection(
                $this->model
                    ->with($withRelations)
                    ->orderByDesc('views')
                    ->limit(5)
                    ->get()
            ),
        ];

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => __('Statistics retrieved successfully.'),
            'data' => $statistics,
        ]);
    }

    public function recommendedProductsList($user, $request): JsonResponse
    {
        $params = $request->all();

        $query = Product::query()
            ->with(['colors', 'sizes', 'images', 'category', 'brand'])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews')
            ->publiclyAvailable();

        if ($user) {
            $categoryCounts = OrderItem::query()
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.user_id', $user->id)
                ->select('products.category_id', DB::raw('SUM(order_items.quantity) as total'))
                ->groupBy('products.category_id')
                ->orderByDesc('total')
                ->pluck('total', 'products.category_id');

            if ($categoryCounts->isNotEmpty()) {
                $topCategoryId = $categoryCounts->keys()->first();
                $query->where('category_id', $topCategoryId);
            } else {
                $query->where('is_suggest', true);
            }
        } else {
            $query->where('is_suggest', true);
        }

        $query->inRandomOrder();

        $data = $query->paginate(10);

        $data->getCollection()->transform(function ($product) {
            $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
            $product->rate_count = $product->reviews_count;
            $product->is_favorite = Auth::check() ? $product->favoritedBy()->where('user_id', Auth::id())->exists() : false;

            return $product;
        });

        return responseHelper(__('Recommended products list fetched successfully.'), 200, ProductResource::collection($data));
    }

    //    public function updatePrices($request)
    //    {
    //        $data = $request->validated();
    //
    //        $isPerc = filter_var($data['is_percentage'] ?? false, FILTER_VALIDATE_BOOLEAN);
    //        $isInc = $data['type'] === 'increment';
    //        $operator = $isInc ? '+' : '-';
    //
    //        $priceVal = (float) ($isPerc ? ($data['percentage'] ?? 0) : ($data['price'] ?? 0));
    //        $discVal = (float) ($isPerc ? ($data['discount_percentage'] ?? 0) : ($data['discount_price'] ?? 0));
    //
    //        if ($priceVal <= 0 && $discVal <= 0) {
    //            return responseHelper(__('No valid values provided.'), 400);
    //        }
    //
    //        return \DB::transaction(function () use ($data, $isPerc, $operator, $priceVal, $discVal) {
    //            $query = \DB::table('products');
    //
    //            if (!empty($data['product_ids'])) {
    //                $query->whereIn('id', $data['product_ids']);
    //            }
    //
    //            $updatePayload = [];
    //
    //            if ($priceVal > 0) {
    //                $priceExpr = $isPerc
    //                    ? "COALESCE(price, 0) * (1 {$operator} ({$priceVal} / 100.0))"
    //                    : "COALESCE(price, 0) {$operator} {$priceVal}";
    //
    //                $updatePayload['price'] = \DB::raw("GREATEST(0, ROUND(({$priceExpr})::numeric, 2))");
    //            }
    //
    //            if ($discVal > 0) {
    //                $discExpr = $isPerc
    //                    ? "COALESCE(discount, 0) * (1 {$operator} ({$discVal} / 100.0))"
    //                    : "COALESCE(discount, 0) {$operator} {$discVal}";
    //
    //                $updatePayload['discount'] = \DB::raw("GREATEST(0, ROUND(({$discExpr})::numeric, 2))");
    //            }
    //
    //            if (empty($updatePayload)) {
    //                return responseHelper(__('Nothing to update.'), 400);
    //            }
    //
    //            $affectedRows = $query->update($updatePayload);
    //
    //            return responseHelper("{$affectedRows} products updated successfully.", 200);
    //        });
    //    }

    public function updatePrices($request)
    {
        $data = $request->validated();

        $isPerc = filter_var($data['is_percentage'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $isInc = ($data['type'] ?? 'increment') === 'increment';
        $operator = $isInc ? '+' : '-';

        $priceVal = (float) ($isPerc ? ($data['percentage'] ?? 0) : ($data['price'] ?? 0));
        $discVal = (float) ($isPerc ? ($data['discount_percentage'] ?? 0) : ($data['discount_price'] ?? 0));

        if ($priceVal <= 0 && $discVal <= 0) {
            return responseHelper(__('No valid values provided.'), 400);
        }

        \Log::warning('Bulk product price update requested.', [
            'user_id' => auth()->id(),
            'all_products' => empty($data['product_ids']),
            'product_ids' => $data['product_ids'] ?? [],
            'type' => $data['type'] ?? null,
            'is_percentage' => $isPerc,
            'price_value' => $priceVal,
            'discount_value' => $discVal,
        ]);

        return \DB::transaction(function () use ($data, $isPerc, $operator, $priceVal, $discVal) {
            $productIds = $data['product_ids'] ?? [];

            $updatePayload = [];
            if ($priceVal > 0) {
                $priceExpr = $isPerc
                    ? "COALESCE(price, 0) * (1 {$operator} ({$priceVal} / 100.0))"
                    : "COALESCE(price, 0) {$operator} {$priceVal}";
                $updatePayload['price'] = \DB::raw("GREATEST(0, ROUND(({$priceExpr})::numeric, 2))");
            }

            if ($discVal > 0) {
                $discExpr = $isPerc
                    ? "COALESCE(discount, 0) * (1 {$operator} ({$discVal} / 100.0))"
                    : "COALESCE(discount, 0) {$operator} {$discVal}";
                $updatePayload['discount'] = \DB::raw("GREATEST(0, ROUND(({$discExpr})::numeric, 2))");
            }

            // --- 2. PİVOT TABLO GÜNCELLEME (Bedenli ürünlerin fiyatları) ---
            // Sadece seçili ürünlerin beden kayıtlarını güncelle
            $pivotQuery = \DB::table('product_size');
            if (! empty($productIds)) {
                $pivotQuery->whereIn('product_id', $productIds);
            }
            $affectedSizes = $pivotQuery->update($updatePayload);

            // --- 3. ANA TABLO GÜNCELLEME (Sadece bedeni OLMAYAN ürünler) ---
            // Burada kritik nokta: product_size tablosunda kaydı olmayan ürünleri bulmalıyız
            $productQuery = \DB::table('products');

            if (! empty($productIds)) {
                $productQuery->whereIn('id', $productIds);
            }

            // Alt sorgu: Eğer ürünün bedeni varsa ana tabloya DOKUNMA
            $productQuery->whereNotExists(function ($q) {
                $q->select(\DB::raw(1))
                    ->from('product_size')
                    ->whereRaw('product_size.product_id = products.id');
            });

            $affectedMainProducts = $productQuery->update($updatePayload);

            return responseHelper(
                "{$affectedMainProducts} standalone products and {$affectedSizes} size variants updated successfully.",
                200
            );
        });
    }

    /** Admin listing query for product story videos (status + text filters). */
    public function storyVideoQuery(Request $request): Builder
    {
        $query = ProductVideo::query()->with('product.images')->latest('id');

        if ($request->filled('status')) {
            $request->query('status') === 'active'
                ? $query->where('is_story_hidden', false)
                : $query->where('is_story_hidden', true);
        }

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($inner) use ($term) {
                $inner->where('video_path', 'like', "%{$term}%")
                    ->orWhere('product_id', $term)
                    ->orWhereHas('product', fn ($p) => $p->where('title->az', 'like', "%{$term}%"));
            });
        }

        return $query;
    }

    public function setStoryVideoActive(int $id, bool $active): void
    {
        ProductVideo::query()->findOrFail($id)->update([
            'is_story_hidden' => ! $active,
            'story_expires_at' => $active ? now()->addDay() : null,
        ]);
    }

    public function removeStoryVideo(int $id): void
    {
        $video = ProductVideo::query()->findOrFail($id);
        $raw = $video->getRawOriginal('video_path');

        if ($raw && ! Str::startsWith($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $video->delete();
    }

    /** Admin product listing query with the panel's search + facet filters. */
    public function adminQuery(Request $request): Builder
    {
        $query = $this->model->newQuery()->with(['images', 'category', 'brand'])->latest('id');

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

    /** Product shown on the admin detail page. */
    public function adminFind(int $id): Product
    {
        return $this->model->newQuery()
            ->with(['images', 'colors', 'sizes', 'category', 'brand', 'videos'])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews')
            ->findOrFail($id);
    }

    /** Product loaded for the edit form (filters + banner handled separately). */
    public function adminFindForEdit(int $id): Product
    {
        return $this->model->newQuery()
            ->with(['images', 'colors', 'sizes', 'videos', 'productFilters', 'category'])
            ->findOrFail($id);
    }

    /** Deletes a product along with its images, colors and sizes. */
    public function adminDelete(int $id): void
    {
        $product = $this->model->newQuery()->with('images')->findOrFail($id);

        foreach ($product->images as $image) {
            $raw = $image->getRawOriginal('image_path');

            if ($raw && ! Str::startsWith($raw, 'http')) {
                Storage::disk('public')->delete($raw);
            }
        }

        $product->colors()->detach();
        $product->sizes()->detach();
        $product->images()->delete();
        $product->delete();
    }

    /** Next free `P000001`-style SKU. */
    public function nextSku(): string
    {
        $last = $this->model->newQuery()->whereNotNull('sku')->orderByDesc('id')->value('sku');
        $next = ($last && preg_match('/P(\d+)/', $last, $m)) ? ((int) $m[1] + 1) : 1;

        do {
            $sku = 'P'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while ($this->model->newQuery()->where('sku', $sku)->exists());

        return $sku;
    }
}
