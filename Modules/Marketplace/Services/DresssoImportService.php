<?php

namespace Modules\Marketplace\Services;

use Illuminate\Support\Facades\DB;

/**
 * One-way adaptation of the legacy Dressso (TypeORM/PostgreSQL) dataset into
 * ShopEra's schema. It reads the old database through a temporary connection
 * layered on the project's own credentials and upserts the rows by natural
 * keys (email, slug, name), so the import can be re-run without duplicates.
 *
 * The legacy project stored every text in a single (Turkish) language, so each
 * imported translation is copied to all four locales; ShopEra's UI then never
 * shows an empty label for an imported row.
 */
class DresssoImportService
{
    private const LOCALES = ['az', 'en', 'ru', 'tr'];

    private const CONNECTION = 'dressso_source';

    /** Dressso store status -> ShopEra vendor status. */
    private const VENDOR_STATUS = [
        'approved' => 'active',
        'pending' => 'pending',
        'rejected' => 'pending',
        'suspended' => 'suspended',
    ];

    /** Dressso order status -> App\Core\Enums\OrderStatus value. */
    private const ORDER_STATUS = [
        'pending_payment' => 4,
        'paid' => 1,
        'preparing' => 1,
        'shipped' => 1,
        'completed' => 2,
        'cancelled' => 6,
        'refunded' => 3,
    ];

    /**
     * How each legacy category lands in ShopEra: reuse an existing category
     * matched by any of its translations, or create one (with a sensible
     * Azerbaijani/English/Russian label) under the given parent slug.
     */
    private const CATEGORY_RULES = [
        'ayaqqabi' => ['match' => ['ayaqqabi', 'shoes']],
        'koynek' => ['match' => ['koynek', 'gomlekler', 'shirts']],
        'kadin' => ['match' => ['kadin', 'women']],
        'elbise' => ['match' => ['elbise', 'elbiseler', 'dresses']],
        'gomlek-bluz' => ['match' => ['gomlek bluz', 'bluzlar', 'blouses']],
        'pantolon' => [
            'match' => ['pantolon'],
            'create' => ['names' => ['az' => 'Şalvar', 'en' => 'Trousers', 'ru' => 'Брюки', 'tr' => 'Pantolon'], 'parent' => 'kadin'],
        ],
        'triko' => [
            'match' => ['triko'],
            'create' => ['names' => ['az' => 'Trikotaj', 'en' => 'Knitwear', 'ru' => 'Трикотаж', 'tr' => 'Triko'], 'parent' => 'kadin'],
        ],
        'dis-giyim' => [
            'match' => ['dis giyim'],
            'create' => ['names' => ['az' => 'Üst geyim', 'en' => 'Outerwear', 'ru' => 'Верхняя одежда', 'tr' => 'Dış Giyim'], 'parent' => 'kadin'],
        ],
        'erkek' => ['match' => ['erkek', 'men']],
        'gomlek' => ['match' => ['gomlek', 'gomlekler', 'shirts']],
        'tisort' => [
            'match' => ['tisort'],
            'create' => ['names' => ['az' => 'Futbolka', 'en' => 'T-shirt', 'ru' => 'Футболка', 'tr' => 'Tişört'], 'parent' => 'erkek'],
        ],
        'aksesuar' => [
            'match' => ['aksesuar', 'accessories'],
            'create' => ['names' => ['az' => 'Aksesuar', 'en' => 'Accessories', 'ru' => 'Аксессуары', 'tr' => 'Aksesuar'], 'parent' => 'Fashion'],
        ],
        'canta' => [
            'match' => ['canta', 'bags'],
            'create' => ['names' => ['az' => 'Çanta', 'en' => 'Bags', 'ru' => 'Сумки', 'tr' => 'Çanta'], 'parent' => 'aksesuar'],
        ],
        'cuzdan' => [
            'match' => ['cuzdan', 'wallets'],
            'create' => ['names' => ['az' => 'Cüzdan', 'en' => 'Wallets', 'ru' => 'Кошельки', 'tr' => 'Cüzdan'], 'parent' => 'aksesuar'],
        ],
    ];

    /** @var array<string, int> Dressso user uuid => ShopEra user id */
    private array $users = [];

    /** @var array<string, int> Dressso store uuid => ShopEra vendor id */
    private array $vendors = [];

    /** @var array<string, int> Dressso store uuid => owner ShopEra user id */
    private array $vendorOwners = [];

    /** @var array<string, int> Dressso brand uuid => ShopEra brand id */
    private array $brands = [];

    /** @var array<string, int> Dressso category uuid => ShopEra category id */
    private array $categories = [];

    /** @var array<string, int> normalized translation => ShopEra category id */
    private array $categoryIndex = [];

    /** @var array<string, int> Dressso category slug => ShopEra category id */
    private array $categoryBySlug = [];

    /** @var array<string, int> Dressso color uuid => ShopEra color id */
    private array $colors = [];

    /** @var array<string, int> Dressso color name (normalized) => ShopEra color id */
    private array $colorsByName = [];

    /** @var array<string, int> Dressso size uuid => ShopEra size id */
    private array $sizes = [];

    /** @var array<string, int> Dressso size label (normalized) => ShopEra size id */
    private array $sizesByName = [];

    /** @var array<string, int> Dressso product uuid => ShopEra product id */
    private array $products = [];

    /** @var array<string, int> Dressso user uuid => default ShopEra address id */
    private array $addressByUser = [];

    /** @var array<string, int> */
    private array $stats = [
        'users_created' => 0,
        'users_linked' => 0,
        'vendors_created' => 0,
        'vendors_updated' => 0,
        'brands_created' => 0,
        'colors_created' => 0,
        'sizes_created' => 0,
        'categories_created' => 0,
        'products_created' => 0,
        'products_updated' => 0,
        'products_skipped' => 0,
        'addresses_created' => 0,
        'orders_created' => 0,
        'orders_skipped' => 0,
        'order_items_created' => 0,
    ];

    /**
     * @return array{stats: array<string, int>, content: array<string, int>}
     */
    public function run(string $sourceDatabase): array
    {
        $base = config('database.connections.'.config('database.default'));

        config(['database.connections.'.self::CONNECTION => array_merge($base, [
            'database' => $sourceDatabase,
        ])]);

        $source = DB::connection(self::CONNECTION);

        DB::transaction(function () use ($source) {
            $this->importUsers($source);
            $this->importVendors($source);
            $this->importBrands($source);
            $this->importColors($source);
            $this->importSizes($source);
            $this->importCategories($source);
            $this->importProducts($source);
            $this->importAddresses($source);
            $this->importOrders($source);
        });

        $content = [
            'banners' => $source->table('banners')->whereNull('deleted_at')->count(),
            'faq_categories' => $source->table('faq_categories')->whereNull('deleted_at')->count(),
            'faqs' => $source->table('faqs')->whereNull('deleted_at')->count(),
            'legal_pages' => $source->table('legal_pages')->whereNull('deleted_at')->count(),
        ];

        DB::purge(self::CONNECTION);

        return ['stats' => $this->stats, 'content' => $content];
    }

    // ---------------------------------------------------------------- users

    private function importUsers($source): void
    {
        foreach ($source->table('users')->whereNull('deleted_at')->orderBy('created_at')->get() as $row) {
            $email = strtolower(trim((string) $row->email));

            if ($email === '' || str_ends_with($email, '@test.local') || $this->isTestName($row->name)) {
                continue;
            }

            $existing = DB::table('users')->where('email', $email)->first();

            if ($existing) {
                // Node's bcrypt writes a $2b$ prefix that PHP's hasher rejects;
                // the algorithm is identical, only the prefix has to change.
                if (str_starts_with((string) $existing->password, '$2b$')) {
                    DB::table('users')->where('id', $existing->id)->update([
                        'password' => $this->passwordHash($row->password_hash),
                    ]);
                }

                $this->users[$row->id] = (int) $existing->id;
                $this->stats['users_linked']++;
                $this->assignRole((int) $existing->id, 'user');

                continue;
            }

            [$name, $surname] = $this->splitName((string) $row->name);

            $id = DB::table('users')->insertGetId([
                'name' => $name,
                'surname' => $surname,
                'phone' => $this->normalizePhone($row->phone) ?? $this->syntheticPhone($email),
                'email' => $email,
                'email_verified_at' => $row->email_verified_at,
                'password' => $this->passwordHash($row->password_hash),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            $this->assignRole($id, 'user');
            $this->users[$row->id] = $id;
            $this->stats['users_created']++;
        }
    }

    // --------------------------------------------------------------- vendors

    private function importVendors($source): void
    {
        foreach ($source->table('stores')->whereNull('deleted_at')->get() as $row) {
            $ownerId = $this->users[$row->owner_user_id] ?? null;

            if ($ownerId === null) {
                continue;
            }

            $payload = [
                'user_id' => $ownerId,
                'slug' => $row->slug,
                'name' => $this->translations($row->name),
                'description' => $this->translationsOrNull($row->description),
                'logo' => $row->logo,
                'phone' => $row->phone,
                'email' => $row->email,
                'status' => self::VENDOR_STATUS[$row->status] ?? 'pending',
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];

            $existing = DB::table('vendors')->where('slug', $row->slug)->first();

            if ($existing) {
                DB::table('vendors')->where('id', $existing->id)->update($payload);
                $vendorId = (int) $existing->id;
                $this->stats['vendors_updated']++;
            } else {
                $vendorId = DB::table('vendors')->insertGetId($payload);
                $this->stats['vendors_created']++;
            }

            $this->vendors[$row->id] = $vendorId;
            $this->vendorOwners[$row->id] = $ownerId;
            $this->assignRole($ownerId, 'vendor');
        }
    }

    // ---------------------------------------------------------------- brands

    private function importBrands($source): void
    {
        foreach ($source->table('brands')->whereNull('deleted_at')->orderBy('sort_order')->get() as $row) {
            if ($this->isTestName($row->name)) {
                continue;
            }

            $existing = DB::table('brands')->where('name', $row->name)->first();

            if ($existing) {
                $this->brands[$row->id] = (int) $existing->id;

                continue;
            }

            $id = DB::table('brands')->insertGetId([
                'name' => $row->name,
                'image' => $row->logo_url,
                'is_active' => true,
                'sort_order' => (int) $row->sort_order,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            $this->brands[$row->id] = $id;
            $this->stats['brands_created']++;
        }
    }

    // ---------------------------------------------------------------- colors

    private function importColors($source): void
    {
        $index = $this->translationIndex('colors', 'name');

        foreach ($source->table('colors')->whereNull('deleted_at')->orderBy('sort_order')->get() as $row) {
            $key = $this->normalize($row->name);
            $id = $index[$key] ?? null;

            if ($id === null) {
                $id = DB::table('colors')->insertGetId([
                    'name' => $this->colorTranslation($row),
                    'hex' => $row->rgba_code,
                    'is_active' => true,
                    'sort_order' => (int) $row->sort_order,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
                $index[$key] = $id;
                $this->stats['colors_created']++;
            }

            $this->colors[$row->id] = (int) $id;
            $this->colorsByName[$key] = (int) $id;
        }
    }

    // ----------------------------------------------------------------- sizes

    private function importSizes($source): void
    {
        $index = $this->translationIndex('sizes', 'name');

        foreach ($source->table('sizes')->whereNull('deleted_at')->orderBy('sort_order')->get() as $row) {
            $key = $this->normalize($row->label);
            $id = $index[$key] ?? null;

            if ($id === null) {
                $id = DB::table('sizes')->insertGetId([
                    'name' => $this->sizeTranslation($row),
                    'is_active' => true,
                    'sort_order' => (int) $row->sort_order,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
                $index[$key] = $id;
                $this->stats['sizes_created']++;
            }

            $this->sizes[$row->id] = (int) $id;
            $this->sizesByName[$key] = (int) $id;
        }
    }

    /**
     * ShopEra already carries the standard clothing/shoe sizes, so only the
     * legacy "one size" entry needs a real multilingual name.
     */
    private function sizeTranslation(object $row): string
    {
        if ($this->normalize($row->label) === 'tek ebat') {
            return json_encode([
                'az' => 'Tək ölçü',
                'en' => 'One Size',
                'ru' => 'Единый размер',
                'tr' => 'Tek Ebat',
            ], JSON_UNESCAPED_UNICODE);
        }

        return $this->translations($row->label);
    }

    // ------------------------------------------------------------ categories

    private function importCategories($source): void
    {
        $this->categoryIndex = $this->translationIndex('categories', 'name');

        foreach (array_keys(self::CATEGORY_RULES) as $slug) {
            $this->categoryForSlug($slug);
        }

        foreach ($source->table('categories')->whereNull('deleted_at')->get() as $row) {
            $id = $this->categoryBySlug[$row->slug] ?? $this->categoryByName($row->name);

            if ($id !== null) {
                $this->categories[$row->id] = $id;
            }
        }
    }

    /**
     * Resolve (or create) the ShopEra category a legacy slug maps to. Parents
     * are resolved first, so the result does not depend on row order.
     */
    private function categoryForSlug(string $slug): ?int
    {
        if (array_key_exists($slug, $this->categoryBySlug)) {
            return $this->categoryBySlug[$slug];
        }

        $rule = self::CATEGORY_RULES[$slug] ?? null;

        if ($rule === null) {
            return null;
        }

        $id = null;

        foreach ($rule['match'] as $name) {
            $id = $this->categoryByName($name) ?? $id;
        }

        if ($id === null && isset($rule['create'])) {
            $parentSlug = $rule['create']['parent'];
            $parentId = isset(self::CATEGORY_RULES[$parentSlug])
                ? $this->categoryForSlug($parentSlug)
                : $this->categoryByName((string) $parentSlug);

            $id = DB::table('categories')->insertGetId([
                'name' => json_encode($rule['create']['names'], JSON_UNESCAPED_UNICODE),
                'parent_id' => $parentId,
                'is_active' => true,
                'sort_order' => 0,
                'needs_brand' => false,
                'show_on_home' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->categoryIndex[$this->normalize($rule['create']['names']['tr'])] = $id;
            $this->stats['categories_created']++;
        }

        $this->categoryBySlug[$slug] = $id;

        return $id;
    }

    private function categoryByName(?string $name): ?int
    {
        return $this->categoryIndex[$this->normalize($name)] ?? null;
    }

    // -------------------------------------------------------------- products

    private function importProducts($source): void
    {
        foreach ($source->table('products')->whereNull('deleted_at')->orderBy('created_at')->get() as $row) {
            if ($this->isTestName($row->name) || $this->isTestName($row->slug)
                || str_starts_with($this->normalize($row->sku), 'media')) {
                $this->stats['products_skipped']++;

                continue;
            }

            $vendorId = $this->vendors[$row->store_id] ?? null;
            $ownerId = $this->vendorOwners[$row->store_id] ?? null;
            $categoryId = $row->category_id !== null
                ? ($this->categories[$row->category_id] ?? null)
                : $this->inferCategory((string) $row->name);

            $payload = [
                'brand_id' => $row->brand_id !== null ? ($this->brands[$row->brand_id] ?? null) : null,
                'category_id' => $categoryId,
                'title' => $this->translations($row->name),
                'description' => $this->translationsOrNull($row->description),
                'gender' => $row->gender,
                'sku' => $row->sku,
                'price' => $row->price,
                'is_active' => (bool) $row->is_active,
                'discount' => $row->discounted_price,
                'stock_count' => (int) $row->stock_quantity,
                'views' => (int) $row->view_count,
                'sales_count' => (int) $row->sales_count,
                'user_id' => $ownerId,
                'vendor_id' => $vendorId,
                'seller_type' => 'vendor',
                'approval_status' => $row->approval_status ?: 'approved',
                'slug' => $row->slug,
                'purchase_limit' => $row->purchase_limit,
                'is_pinned' => (bool) $row->is_pinned,
                'discount_expire_date' => $row->discount_expires_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];

            $existing = DB::table('products')->where('slug', $row->slug)->first();

            if ($existing) {
                DB::table('products')->where('id', $existing->id)->update($payload);
                $productId = (int) $existing->id;
                $this->stats['products_updated']++;
            } else {
                $productId = DB::table('products')->insertGetId($payload);
                $this->stats['products_created']++;
            }

            $this->products[$row->id] = $productId;
            $this->syncVariants($source, $row, $productId);
        }
    }

    /**
     * Dressso keeps one row per colour+size combination; ShopEra stores the
     * colour and size sets plus a price per size, so the combinations collapse
     * into the two pivot tables while the product keeps the total stock.
     */
    private function syncVariants($source, object $product, int $productId): void
    {
        $variants = $source->table('product_variants')
            ->where('product_id', $product->id)
            ->whereNull('deleted_at')
            ->get();

        foreach ($variants as $variant) {
            $colorId = $this->colors[$variant->color_id] ?? null;

            if ($colorId !== null) {
                DB::table('color_product')->updateOrInsert(
                    ['product_id' => $productId, 'color_id' => $colorId],
                    []
                );
            }

            $sizeId = $this->sizes[$variant->size_id] ?? null;

            if ($sizeId !== null) {
                DB::table('product_size')->updateOrInsert(
                    ['product_id' => $productId, 'size_id' => $sizeId],
                    [
                        'price' => $variant->price ?? $product->price,
                        'discount' => $variant->discounted_price ?? $product->discounted_price,
                        'wholesale_price' => null,
                    ]
                );
            }
        }
    }

    // ------------------------------------------------------------- addresses

    private function importAddresses($source): void
    {
        $cities = $source->table('cities')->pluck('name', 'id');
        $districts = $source->table('districts')->pluck('name', 'id');

        foreach ($source->table('addresses')->whereNull('deleted_at')->get() as $row) {
            $userId = $this->users[$row->user_id] ?? null;

            if ($userId === null) {
                continue;
            }

            $street = trim((string) $row->address_line);

            $existing = DB::table('user_addresses')
                ->where('user_id', $userId)
                ->where('street_building_number', $street)
                ->first();

            if ($existing) {
                $this->addressByUser[$row->user_id] = (int) $existing->id;

                continue;
            }

            $id = DB::table('user_addresses')->insertGetId([
                'user_id' => $userId,
                'city' => (string) ($cities[$row->city_id] ?? ''),
                'town_village_district' => (string) ($districts[$row->district_id] ?? ''),
                'street_building_number' => $street,
                'unit_floor_apartment' => $this->apartmentFromAddress($street),
                'is_default' => (bool) $row->is_default,
                'full_name' => (string) $row->recipient_name,
                'contact_number' => (string) $this->normalizePhone($row->phone),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            $this->addressByUser[$row->user_id] = $id;
            $this->stats['addresses_created']++;
        }
    }

    // ---------------------------------------------------------------- orders

    private function importOrders($source): void
    {
        foreach ($source->table('orders')->whereNull('deleted_at')->orderBy('created_at')->get() as $row) {
            $userId = $this->users[$row->user_id] ?? null;

            if ($userId === null) {
                continue;
            }

            $storeOrderIds = $source->table('store_orders')->where('order_id', $row->id)->pluck('id');

            $items = $source->table('order_items')
                ->whereIn('store_order_id', $storeOrderIds)
                ->whereNull('deleted_at')
                ->get()
                ->filter(fn ($item) => isset($this->products[$item->product_id]))
                ->values();

            if ($items->isEmpty()) {
                $this->stats['orders_skipped']++;

                continue;
            }

            $payment = $source->table('payments')
                ->where('order_id', $row->id)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->first();

            $addressId = $this->addressByUser[$row->user_id] ?? null;

            $payload = [
                'user_id' => $userId,
                'address_id' => $addressId,
                'transaction_id' => $row->order_number,
                'total_price' => $row->grand_total,
                'discount_price' => $row->discount_total,
                'shipping_price' => $row->shipping_total,
                'paid_at' => $row->paid_at,
                'note' => $row->note,
                'address_type' => '0',
                'address_type_id' => $addressId !== null ? (string) $addressId : null,
                'payment_type' => $payment ? $this->paymentType($payment) : null,
                'pricing_type' => 'retail',
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];

            $existing = DB::table('orders')->where('transaction_id', $row->order_number)->first();

            if ($existing) {
                DB::table('orders')->where('id', $existing->id)->update($payload);
                $orderId = (int) $existing->id;
            } else {
                $orderId = DB::table('orders')->insertGetId($payload);
                $this->stats['orders_created']++;
            }

            foreach ($items as $item) {
                $productId = $this->products[$item->product_id];

                $exists = DB::table('order_items')
                    ->where('order_id', $orderId)
                    ->where('product_id', $productId)
                    ->where('quantity', $item->quantity)
                    ->where('unit_price', $item->unit_price)
                    ->exists();

                if ($exists) {
                    continue;
                }

                [$colorId, $sizeId] = $this->variantIds($item->variant_label);

                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'color_id' => $colorId,
                    'size_id' => $sizeId,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->line_total,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]);
                $this->stats['order_items_created']++;
            }

            $status = self::ORDER_STATUS[$row->status] ?? 4;

            DB::table('order_statuses')->updateOrInsert(
                ['order_id' => $orderId, 'status' => (string) $status],
                ['created_at' => $row->created_at, 'updated_at' => $row->updated_at]
            );
        }
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function variantIds(?string $label): array
    {
        if ($label === null || trim($label) === '') {
            return [null, null];
        }

        $parts = array_map('trim', explode('/', $label));

        return [
            $this->colorsByName[$this->normalize($parts[0] ?? '')] ?? null,
            $this->sizesByName[$this->normalize($parts[1] ?? '')] ?? null,
        ];
    }

    private function paymentType(object $payment): string
    {
        return $payment->method === 'card' ? 'CARD' : 'CASH';
    }

    // --------------------------------------------------------------- helpers

    /**
     * Build a "normalized translation => id" index for a translatable column.
     *
     * @return array<string, int>
     */
    private function translationIndex(string $table, string $column): array
    {
        $index = [];

        foreach (DB::table($table)->whereNull('deleted_at')->get(['id', $column]) as $row) {
            $value = $row->{$column};
            $translations = is_string($value)
                ? (json_decode($value, true) ?: [$value])
                : (array) $value;

            foreach (self::LOCALES as $locale) {
                $label = $translations[$locale] ?? null;

                if (is_string($label) && trim($label) !== '') {
                    $index[$this->normalize($label)] = (int) $row->id;
                }
            }
        }

        return $index;
    }

    /**
     * JSON-encode the same text for every supported locale.
     */
    private function translations(?string $value): string
    {
        $value = trim((string) $value);

        return json_encode(array_fill_keys(self::LOCALES, $value), JSON_UNESCAPED_UNICODE);
    }

    private function translationsOrNull(?string $value): ?string
    {
        return trim((string) $value) === '' ? null : $this->translations($value);
    }

    private function colorTranslation(object $row): string
    {
        $names = [
            'bordo' => ['az' => 'Tünd qırmızı', 'en' => 'Burgundy', 'ru' => 'Бордовый', 'tr' => 'Bordo'],
        ][$this->normalize($row->name)] ?? array_fill_keys(self::LOCALES, trim((string) $row->name));

        return json_encode($names, JSON_UNESCAPED_UNICODE);
    }

    private function normalize(?string $value): string
    {
        $value = strtr(mb_strtolower(trim((string) $value)), [
            'ə' => 'e', 'ğ' => 'g', 'ş' => 's', 'ç' => 'c', 'ö' => 'o', 'ü' => 'u', 'ı' => 'i',
        ]);

        return trim((string) preg_replace('/[^a-z0-9]+/u', ' ', $value));
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return [null, null];
        }

        $surname = count($parts) > 1 ? array_pop($parts) : null;

        return [implode(' ', $parts), $surname];
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits === '' ? null : $digits;
    }

    /**
     * The users table requires a phone number, but some legacy accounts have
     * none. Derive a stable placeholder so re-running the import is safe.
     */
    private function syntheticPhone(string $email): string
    {
        return '000'.str_pad((string) (crc32($email) % 10000000), 7, '0', STR_PAD_LEFT);
    }

    /**
     * PHP's bcrypt verifier only accepts the $2y$ prefix, while Node's bcrypt
     * writes $2b$ for the same algorithm.
     */
    private function passwordHash(string $hash): string
    {
        return str_starts_with($hash, '$2b$') ? '$2y$'.substr($hash, 4) : $hash;
    }

    private function apartmentFromAddress(string $address): string
    {
        if (preg_match('/D\s*[:.]?\s*([0-9A-Za-z\-]+)\s*$/u', $address, $matches)) {
            return 'D:'.$matches[1];
        }

        return '—';
    }

    private function isTestName(?string $name): bool
    {
        return str_contains($this->normalize($name), 'test');
    }

    /**
     * Products without a legacy category get a sensible one from their title.
     */
    private function inferCategory(string $name): ?int
    {
        $name = $this->normalize($name);

        $rules = [
            'elbise' => 'elbise',
            'gomlek' => 'koynek',
            'bluz' => 'gomlek-bluz',
            'palto' => 'dis-giyim',
            'mont' => 'dis-giyim',
            'pantolon' => 'pantolon',
            'kazak' => 'triko',
            'tisort' => 'tisort',
            'canta' => 'canta',
            'cuzdan' => 'cuzdan',
        ];

        foreach ($rules as $needle => $slug) {
            if (str_contains($name, $needle)) {
                return $this->categoryBySlug[$slug] ?? null;
            }
        }

        return null;
    }

    private function assignRole(int $userId, string $roleName): void
    {
        $roleId = DB::table('roles')->where('name', $roleName)->value('id');

        if ($roleId === null) {
            return;
        }

        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id' => $roleId,
                'model_type' => 'Modules\\User\\Entities\\User',
                'model_id' => $userId,
            ],
            []
        );
    }
}
