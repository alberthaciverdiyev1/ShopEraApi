<?php

namespace Modules\Store\Http\Controllers;

use App\Services\Notification\AdminNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Requests\ProductAddRequest;
use Modules\Product\Http\Requests\ProductUpdateRequest;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Services\ProductService;
use Modules\Store\Http\Entities\Store;

class StoreProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request)
    {
        $store = $this->activeStore($request);
        $query = Product::with(['colors', 'sizes', 'images', 'videos', 'category', 'brand'])
            ->where('store_id', $store->id)->latest();
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->string('approval_status'));
        }
        $items = $query->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        return responseHelper(__('Store products retrieved successfully.'), 200, ProductResource::collection($items));
    }

    public function store(ProductAddRequest $request)
    {
        $store = $this->activeStore($request);

        // A draft stays with the seller: not in the queue, not in the catalogue.
        if ($request->boolean('is_draft')) {
            return $this->products->add($request, [
                'store_id' => $store->id,
                'user_id' => $request->user()->id,
                'approval_status' => 'draft',
                'approved_at' => null,
                'is_active' => false,
                // Never the seller's to set — see the note in update().
                'is_pinned' => false,
                'is_suggest' => false,
                'views' => 0,
                'sales_count' => 0,
            ]);
        }

        $approved = $store->is_trusted;

        $response = $this->products->add($request, [
            'store_id' => $store->id,
            'user_id' => $request->user()->id,
            'approval_status' => $approved ? 'approved' : 'pending',
            'approved_at' => $approved ? now() : null,
            'last_approved_at' => $approved ? now() : null,
            'is_active' => $approved,
            // Never the seller's to set — see the note in update().
            'is_pinned' => false,
            'is_suggest' => false,
            'views' => 0,
            'sales_count' => 0,
        ]);

        if (! $approved) {
            $this->notifyAdminsOfNewProduct($store, $response);
        }

        return $response;
    }

    /** Sends a draft or a rejected product (back) into the moderation queue. */
    public function submit(Request $request, int $id)
    {
        $store = $this->activeStore($request);
        $product = Product::where('store_id', $store->id)
            ->whereIn('approval_status', ['draft', 'rejected', 'unpublished'])
            ->findOrFail($id);

        $approved = $store->is_trusted;
        $product->update([
            'approval_status' => $approved ? 'approved' : 'pending',
            'approved_at' => $approved ? now() : null,
            // Never cleared. An 'unpublished' product coming back through here
            // was certainly live once, and that is what tells the admin this is
            // a returning product rather than a new one.
            'last_approved_at' => $approved ? now() : $product->last_approved_at,
            'approved_by' => null,
            'rejection_reason' => null,
            'is_active' => $approved,
        ]);

        if (! $approved) {
            $this->notifyAdminsOfPendingProduct($store, $product->fresh());
        }

        return responseHelper(__('Product submitted for review.'), 200, ProductResource::make($product->fresh()));
    }

    /**
     * Deletes one of the store's own products.
     *
     * The route has always existed and pointed at a method that did not, so
     * every delete from the store app came back as a server error. Scoped to
     * the caller's store: without that, a seller could pass any product id and
     * delete somebody else's stock.
     */
    public function destroy(Request $request, int $id)
    {
        $store = $this->activeStore($request);

        $product = Product::where('store_id', $store->id)->findOrFail($id);

        return $this->products->delete($product->id);
    }

    /** Temporarily takes an approved product out of the catalogue, keeping it. */
    public function unpublish(Request $request, int $id)
    {
        $store = $this->activeStore($request);
        $product = Product::where('store_id', $store->id)
            ->where('approval_status', 'approved')
            ->findOrFail($id);

        $product->update(['approval_status' => 'unpublished', 'is_active' => false]);

        return responseHelper(__('Product unpublished successfully.'), 200, ProductResource::make($product->fresh()));
    }

    /**
     * Fields whose change puts the product back in the moderation queue. Stock is
     * deliberately absent: restocking is an operational action and must not pull
     * a live product out of the catalogue while it waits for an admin.
     */
    private const MODERATED_FIELDS = [
        'title', 'description', 'price', 'wholesale_price', 'discount',
        'category_id', 'brand_id', 'gender', 'sku', 'weight',
        'images', 'videos', 'existing_images', 'existing_videos', 'colors', 'sizes',
    ];

    public function update(ProductUpdateRequest $request, int $id)
    {
        $this->assertBodyWasParsed($request);

        $store = $this->activeStore($request);
        $product = Product::with(['colors', 'sizes'])->where('store_id', $store->id)->findOrFail($id);

        $overrides = [
            'user_id' => $request->user()->id,
            'store_id' => $store->id,
        ];

        if ($store->is_trusted) {
            $overrides += [
                'approval_status' => 'approved',
                'approved_at' => now(),
                'last_approved_at' => now(),
                'approved_by' => null,
                'rejection_reason' => null,
                'is_active' => true,
            ];
        } elseif ($this->touchesModeratedFields($request, $product)) {
            $overrides += [
                'approval_status' => 'pending',
                'approved_at' => null,
                // Carried across the edit on purpose. Nulling approved_at is
                // what erased every trace that this product had been live, and
                // an admin reading a queue row with an old creation date and no
                // history takes it for a duplicate — which is how the same
                // products got rejected two and three times.
                'last_approved_at' => $product->approved_at ?? $product->last_approved_at,
                'approved_by' => null,
                'rejection_reason' => null,
                'is_active' => false,
            ];
        }
        // Otherwise nothing about approval changes — a stock-only edit keeps the
        // product exactly as approved and visible as it was.

        // Placement and analytics belong to TeymurStore, not to the seller, and
        // the update request accepts all of them. Pinning your own product to
        // the top of the catalogue or writing your own sales_count is not an
        // edit, it is helping yourself. The current values are re-asserted
        // rather than reset, so an admin's pin survives the seller's edit.
        //
        // `+=` on purpose: the branches above already decided is_active where
        // they had an opinion, and this must not overrule them. It only covers
        // the stock-only path, where a seller could otherwise re-activate a
        // product an admin had taken down — `unpublish()` is the way to hide
        // one's own product.
        $overrides += [
            'is_pinned' => $product->is_pinned,
            'is_suggest' => $product->is_suggest,
            'views' => $product->views,
            'sales_count' => $product->sales_count,
            'is_active' => $product->is_active,
        ];

        return $this->products->update($request, $id, $overrides);
    }

    private function touchesModeratedFields(ProductUpdateRequest $request, Product $product): bool
    {
        $data = $request->validated();

        // A client that says it sent its complete list, but sent no key because
        // the list is empty, means an empty list — not silence.
        if ($request->boolean('colors_synced') && ! array_key_exists('colors', $data)) {
            $data['colors'] = [];
        }

        if ($request->boolean('images_synced') && ! array_key_exists('existing_images', $data)) {
            $data['existing_images'] = [];
        }

        foreach (self::MODERATED_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            // Not true of the app. It resends colors and existing_images on
            // every save, so treating their presence as a change sent even a
            // pure stock edit back into moderation and pulled a live product
            // out of the catalogue until an admin looked at it. The exemption
            // this class documents was unreachable in production. Compare what
            // arrived against what is stored instead.
            if (in_array($field, ['images', 'videos'], true)) {
                // Genuinely new uploads; an empty array is not a change.
                if (! empty($data[$field])) {
                    return true;
                }

                continue;
            }

            if ($field === 'colors') {
                if ($this->idSetChanged($data[$field], $product->colors->pluck('id')->all())) {
                    return true;
                }

                continue;
            }

            if ($field === 'sizes') {
                if ($this->sizesChanged($product, $data[$field])) {
                    return true;
                }

                continue;
            }

            if ($field === 'existing_images') {
                if ($this->existingImagesChanged($product, $data[$field])) {
                    return true;
                }

                continue;
            }

            if ($field === 'existing_videos') {
                $keptIds = collect($data[$field] ?? [])
                    ->map(fn ($row) => (int) (is_array($row) ? ($row['id'] ?? 0) : $row))
                    ->all();

                if ($this->idSetChanged($keptIds, $product->videos()->pluck('id')->all())) {
                    return true;
                }

                continue;
            }

            // title/description arrive as a per-language map, while the model
            // accessor returns just the active locale's string — comparing the
            // two always differed, so every edit looked like a content change
            // and even a stock-only save went back into moderation.
            if (in_array($field, ['title', 'description'], true)) {
                if ($this->translationsChanged($product, $field, $data[$field])) {
                    return true;
                }

                continue;
            }

            // gender arrives as a word and is stored as the enum's int, so a
            // plain compare said "male" !== "0" on every single save and the
            // exemption this class documents never fired for any product that
            // has a gender — which is most of them.
            if ($field === 'gender') {
                if ($this->genderChanged($data[$field], $product->getAttribute($field))) {
                    return true;
                }

                continue;
            }

            if ($this->valueChanged($field, $data[$field], $product->getAttribute($field))) {
                return true;
            }
        }

        return false;
    }

    /** Both sides reduced to the enum's int before comparing. */
    private function genderChanged($incoming, $current): bool
    {
        $toInt = function ($value): ?int {
            if ($value === null || $value === '') {
                return null;
            }

            if (is_numeric($value)) {
                return (int) $value;
            }

            try {
                return \App\Enums\Gender::fromString((string) $value)->value;
            } catch (\InvalidArgumentException) {
                // An unrecognised word is not a gender we can compare, so treat
                // it as a change and let validation deal with it.
                return -1;
            }
        };

        return $toInt($incoming) !== $toInt($current);
    }

    /** Order and duplicates are irrelevant — only membership decides a change. */
    private function idSetChanged($incoming, array $current): bool
    {
        $submitted = collect((array) $incoming)
            ->map(fn ($value) => (int) (is_array($value) ? ($value['id'] ?? 0) : $value))
            ->filter()
            ->unique()->sort()->values()->all();

        $stored = collect($current)->map(fn ($value) => (int) $value)
            ->filter()->unique()->sort()->values()->all();

        return $submitted !== $stored;
    }

    /**
     * A size row carries its own prices, so the set of ids is not enough - a
     * price change on one size is very much a moderated change.
     *
     * Compared against what the API actually published, not the raw pivot.
     * ProductResource hands the client a RESOLVED price (the pivot value or
     * the product's own) and a resolved discount (0.0 when there is no valid
     * one), and the client echoes those back. Comparing them to the NULL pivot
     * columns they came from called every sized product changed on every save.
     */
    private function sizesChanged(Product $product, $incoming): bool
    {
        $pricing = app(\Modules\Product\Services\ProductPricingService::class);
        $num = fn ($value) => $value === null || $value === '' ? null : round((float) $value, 2);

        $submitted = [];
        foreach ((array) $incoming as $row) {
            if (is_array($row) && isset($row['size_id'])) {
                $submitted[(int) $row['size_id']] = $row;
            }
        }

        $submittedIds = array_keys($submitted);
        $storedIds = $product->sizes->map(fn ($size) => (int) $size->id)->all();
        sort($submittedIds);
        sort($storedIds);

        if ($submittedIds !== $storedIds) {
            return true;
        }

        foreach ($product->sizes as $size) {
            $row = $submitted[(int) $size->id];
            $prices = $pricing->retailPrices($product, $size->id);

            $storedPrice = $num($prices['original_price']);
            $storedWholesale = $num($size->pivot->wholesale_price ?? null);
            $storedDiscount = $num($prices['discounted_price']);

            // An omitted field means the client had nothing to say about it,
            // which is not the same as clearing it.
            if (($num($row['price'] ?? null) ?? $storedPrice) !== $storedPrice) {
                return true;
            }

            if (($num($row['wholesale_price'] ?? null) ?? $storedWholesale) !== $storedWholesale) {
                return true;
            }

            if (($num($row['discount'] ?? null) ?? $storedDiscount) !== $storedDiscount) {
                return true;
            }
        }

        return false;
    }

    /**
     * Which photos were kept, and which colour each shows. Reassigning a photo
     * to a different colour changes what the shopper sees, so it is moderated
     * like any other content change.
     */
    private function existingImagesChanged(Product $product, $incoming): bool
    {
        $submitted = collect((array) $incoming)
            ->filter(fn ($row) => is_array($row) && isset($row['id']))
            ->mapWithKeys(fn ($row) => [
                (int) $row['id'] => isset($row['color_id']) ? (int) $row['color_id'] : null,
            ])
            ->sortKeys()->all();

        $stored = $product->images()
            ->pluck('color_id', 'id')
            ->map(fn ($colorId) => $colorId === null ? null : (int) $colorId)
            ->sortKeys()->all();

        return $submitted !== $stored;
    }

    /** Money and measures are stored as decimals, so compare them numerically. */
    private const NUMERIC_FIELDS = ['price', 'wholesale_price', 'discount', 'weight'];

    private function valueChanged(string $field, $incoming, $current): bool
    {
        // Money first, before the null check. The resource publishes resolved
        // prices, so a product with a NULL discount column is handed 0.0 and
        // sends 0.0 straight back; the null branch below then called that a
        // change on every single save. For a number, absent and zero are the
        // same amount.
        if (in_array($field, self::NUMERIC_FIELDS, true)) {
            // "15" and "15.00" are the same price; a string compare called every
            // save a price change and pushed stock-only edits into moderation.
            return abs((float) $incoming - (float) $current) > 0.0001;
        }

        if ($incoming === null || $current === null) {
            return $incoming !== $current;
        }

        return (string) $incoming !== (string) $current;
    }

    /**
     * True when the request carries a different text for any language it sends.
     * Languages the request omits are left out of the comparison.
     */
    private function translationsChanged(Product $product, string $field, $incoming): bool
    {
        if (! is_array($incoming)) {
            return true;
        }

        $current = $product->getTranslations($field);

        foreach ($incoming as $locale => $text) {
            // The service lowercases what it stores, so compare on equal terms.
            if (Str::lower((string) $text) !== Str::lower((string) ($current[$locale] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    /**
     * PHP only fills a request body for POST, so a multipart PUT can never be
     * read no matter what it carries: validation sees nothing, nothing is
     * written and the client is told 200. A seller edited a product name in
     * the app, was told it went for review, and found the old value intact.
     *
     * The shape is structurally impossible to honour, so refuse it outright
     * rather than inspecting the payload — the request bag is not empty either
     * way, since ProductUpdateRequest injects its own fields before validation.
     */
    private function assertBodyWasParsed(Request $request): void
    {
        $isMultipart = str_contains(
            (string) $request->header('Content-Type'),
            'multipart/form-data'
        );

        // getRealMethod() so a POST spoofing PUT — the supported path — passes.
        if ($isMultipart && $request->getRealMethod() === 'PUT') {
            abort(422, __('Məhsulu yeniləmək üçün POST və _method=PUT istifadə edin.'));
        }
    }

    private function activeStore(Request $request): Store
    {
        $store = Store::where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($store->status === 'approved' && $store->is_active, 403, __('Mağazanız hazırda aktiv deyil.'));

        return $store;
    }

    /**
     * Same alert for a brand new product, read back from the create response.
     */
    private function notifyAdminsOfNewProduct($store, $response): void
    {
        try {
            $payload = is_object($response) && method_exists($response, 'getData')
                ? $response->getData(true)
                : [];

            $productId = $payload['data']['id'] ?? null;
            $product = $productId ? Product::find($productId) : null;

            if ($product) {
                $this->notifyAdminsOfPendingProduct($store, $product);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Tells staff a product is waiting in the moderation queue.
     */
    private function notifyAdminsOfPendingProduct($store, Product $product): void
    {
        app(AdminNotifier::class)->notify(
            __('Təsdiq gözləyən məhsul'),
            __('":store" mağazası ":name" məhsulunu yoxlanışa göndərdi.', [
                'store' => $store->name,
                'name' => (string) $product->title,
            ]),
            [
                'type' => 'admin_product_pending',
                'product_id' => (string) $product->id,
                'store_id' => (string) $store->id,
            ],
        );
    }
}
