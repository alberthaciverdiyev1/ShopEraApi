<?php

namespace Modules\Product\Services;

use App\Enums\Gender;
use Google\Service\Logging\Resource\Logs;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Category\Http\Entities\Category;
use Modules\Notification\Services\SendNotificationService;
use Modules\Order\Http\Entities\OrderItem;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Entities\ProductImage;
use Modules\Product\Http\Entities\ProductVideo;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Http\Resources\ProductStoryVideoResource;
use Illuminate\Support\Str;
use App\Helpers\TranslateHelper as Translate;
use Modules\User\Http\Entities\Basket;

class ProductService
{
    private Product $model;
    private ProductSubscribeService $subscribe_service;

    /**
     * @param Product $model
     */
    function __construct(Product $model, ProductSubscribeService $subscribe_service)
    {
        $this->model = $model;
        $this->subscribe_service = $subscribe_service;
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function list($request): JsonResponse
    {
        $params = $request->all();
        $locale = app()->getLocale();

        $discountCount = Product::whereNotNull('discount')
            ->publiclyAvailable()
            ->count();

        $query = Product::query()
            ->with(['colors', 'sizes', 'images', 'videos', 'category', 'brand', 'store'])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews');

        $isAdminRequest = !empty($params['is_admin']) && auth('sanctum')->check();

        if ($isAdminRequest && isset($params['is_active'])) {
            $query->where('is_active', $params['is_active']);
        } elseif ($isAdminRequest) {
            $query->where('is_active', 1);
        } else {
            $query->publiclyAvailable();
        }

        // Admin needs to tell its own catalogue apart from marketplace stock;
        // absent the filter nothing changes, so existing callers are unaffected.
        if (isset($params['source'])) {
            if ($params['source'] === 'own') {
                $query->whereNull('store_id');
            } elseif ($params['source'] === 'store') {
                $query->whereNotNull('store_id');
            }
        }

        if (!empty($params['store_id'])) {
            $query->where('store_id', $params['store_id']);
        }

        if ($isAdminRequest && !empty($params['approval_status'])) {
            $query->where('approval_status', $params['approval_status']);
        }

        if (isset($params['is_suggest'])) {
            $query->where('is_suggest', $params['is_suggest']);
        }

        if (!empty($params['discount'])) {
            $query->whereNotNull('discount');
        }

        if (!empty($params['gender']) && in_array($params['gender'], ['male', 'female', 'kids'])) {
            $query->where('gender', Gender::fromString($params['gender'])->value);
        }

        rangeFilter($query, 'price', $params);

        if (!empty($params['category_ids']) && is_array($params['category_ids'])) {

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


        if (!empty($params['brand_ids']) && is_array($params['brand_ids'])) {
            $query->whereIn('brand_id', $params['brand_ids']);
        }

        if (!empty($params['color_ids']) && is_array($params['color_ids'])) {
            $query->whereHas('colors', fn($q) => $q->whereIn('colors.id', $params['color_ids']));
        }

        if (!empty($params['size_ids']) && is_array($params['size_ids'])) {
            $query->whereHas('sizes', fn($q) => $q->whereIn('sizes.id', $params['size_ids']));
        }

        if (!empty($params['search'])) {
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

        if (!empty($params['is_admin'])) {
            orderBy($query, $params);
        } elseif (!$isFiltered) {
            if ($pinnedFirst) {
                $query->orderByDesc('is_pinned');
            }

            $query->inRandomOrder();
        } else {
            if ($pinnedFirst) {
                $query->orderByDesc('is_pinned');
            }

            if ($prioritiseOwnProducts) {
                $query->orderByRaw('CASE WHEN store_id IS NULL THEN 0 ELSE 1 END');
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
     * Product details
     */
    public function details(int $id): JsonResponse
    {
        try {
            $product = $this->model->with([
                'colors', 'sizes', 'images', 'videos', 'category', 'brand', 'store', 'reviews.user'
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

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return responseHelper(__('Product not found.'), 403, []);
        }
    }

    public function storyVideos(): JsonResponse
    {
        $videos = ProductVideo::query()
            ->with([
                'product' => fn($query) => $query
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
            ->whereHas('product', fn($query) => $query->publiclyAvailable())
            ->latest()
            ->get();

        return responseHelper(__('Product story videos retrieved successfully.'),
            200,
            ProductStoryVideoResource::collection($videos)
        );
    }

    public function storyVideosAdmin($request): JsonResponse
    {
        $search = trim((string)$request->query('search', ''));
        $isNumericSearch = ctype_digit($search);
        $locale = app()->getLocale();

        $limit = (int)$request->query('limit', 0);

        $videos = ProductVideo::query()
            ->with(['product.images'])
            ->when($search !== '', function ($query) use ($search, $isNumericSearch, $locale) {
                $query->where(function ($query) use ($search, $isNumericSearch, $locale) {
                    if ($isNumericSearch) {
                        $query
                            ->where('id', (int)$search)
                            ->orWhere('product_id', (int)$search)
                            ->orWhere('video_path', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($productQuery) use ($search, $locale) {
                                $productQuery
                                    ->whereRaw("unaccent(title->>?) ILIKE unaccent(?)", [$locale, "%{$search}%"])
                                    ->orWhereRaw("unaccent(title->>'az') ILIKE unaccent(?)", ["%{$search}%"]);
                            });
                    } else {
                        $query
                            ->where('video_path', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($productQuery) use ($search, $locale) {
                                $productQuery
                                    ->whereRaw("unaccent(title->>?) ILIKE unaccent(?)", [$locale, "%{$search}%"])
                                    ->orWhereRaw("unaccent(title->>'az') ILIKE unaccent(?)", ["%{$search}%"]);
                            });
                    }
                });
            })
            ->when($limit > 0, fn($query) => $query->limit($limit))
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
                'colors', 'sizes', 'images', 'videos', 'category', 'brand', 'store', 'reviews.user'
            ])->findOrFail($id);

            $averageRate = $product->reviews()->avg('rate') ?? 5;
            $data = ProductResource::make($product);
            $data->rate = round($averageRate, 2);


            return response()->json([
                'success' => 200,
                'message' => __('Product details retrieved successfully.'),
                'data' => $product,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
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
        $lock = Cache::lock('add_product_' . auth()->id(), 10);
        $productHash = '';

        if (!$lock->get()) {
            return responseHelper("The process is in progress, please wait.",429);
        }
        try {
            $data = array_merge($request->validated(), $overrides);

            $productHash = md5($data['title']['az'] . ($data['category_id'] ?? ''));

            if (Cache::has('processing_product_' . $productHash)) {
                return responseHelper("This product is already being registered.",429);
            }


            Cache::put('processing_product_' . $productHash, true, 5);

            if (!isset($data['sku'])) {
                $lastProduct = $this->model->orderByDesc('id')->first();
                if ($lastProduct && preg_match('/P(\d+)/', $lastProduct->sku, $matches)) {
                    $nextNumber = (int)$matches[1] + 1;
                } else {
                    $nextNumber = 1;
                }

                $data['sku'] = 'P' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

                while ($this->model->where('sku', $data['sku'])->exists()) {
                    $nextNumber++;
                    $data['sku'] = 'P' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
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

                if (!empty($colors)) {
                    $product->colors()->sync($colors);
                }

                if (!empty($sizesData)) {
                    $syncSizes = [];
                    foreach ($sizesData as $item) {
                        $syncSizes[$item['size_id']] = [
                            'price' => $item['price'] ?? null,
                            'wholesale_price' => $item['wholesale_price'] ?? null,
                            'discount' => $item['discount'] ?? null
                        ];
                    }
                    $product->sizes()->sync($syncSizes);
                }

                if (!empty($imageFiles)) {
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


                if (!empty($videos_arr) && is_array($videos_arr)) {
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
            Cache::forget('processing_product_' . $productHash);
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
                            'discount' => $item['discount'] ?? null
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
                    ->when(!empty($existingImageIds), fn($q) => $q->whereNotIn('id', $existingImageIds))
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

            if (!empty($imageFiles)) {
                foreach ($imageFiles as $index => $imageArray) {
                    $file = is_array($imageArray) ? ($imageArray['file'] ?? null) : $imageArray;
                    if (!$file) continue;
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
                    ->when(!empty($existingVideoIds), fn($q) => $q->whereNotIn('id', $existingVideoIds))
                    ->get();

                foreach ($videosToDelete as $video) {
                    $videoPath = ltrim(str_replace($cdnBaseUrl, '', $video->video_path), '/');
                    if (Storage::disk('bunnycdn')->exists($videoPath)) {
                        Storage::disk('bunnycdn')->delete($videoPath);
                    }
                    $video->delete();
                }
            }

            if (!empty($videoFiles)) {
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

        $priceVal = (float)($isPerc ? ($data['percentage'] ?? 0) : ($data['price'] ?? 0));
        $discVal = (float)($isPerc ? ($data['discount_percentage'] ?? 0) : ($data['discount_price'] ?? 0));

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
            if (!empty($productIds)) {
                $pivotQuery->whereIn('product_id', $productIds);
            }
            $affectedSizes = $pivotQuery->update($updatePayload);

            // --- 3. ANA TABLO GÜNCELLEME (Sadece bedeni OLMAYAN ürünler) ---
            // Burada kritik nokta: product_size tablosunda kaydı olmayan ürünleri bulmalıyız
            $productQuery = \DB::table('products');

            if (!empty($productIds)) {
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
}
