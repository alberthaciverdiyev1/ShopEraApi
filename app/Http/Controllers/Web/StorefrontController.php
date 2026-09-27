<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Modules\Banner\Http\Entities\Banner;
use Modules\Category\Http\Entities\Category;
use Modules\HelpAndPolicy\Http\Entities\Faq;
use Modules\HelpAndPolicy\Http\Entities\LegalTerm;
use Modules\Product\Http\Entities\Product;
use Modules\Setting\Http\Entities\Setting;

class StorefrontController extends Controller
{
    public function home(Request $request): View
    {
        $featuredProducts = $this->productQuery()
            ->where(function (Builder $query): void {
                $query->whereNotNull('discount')
                    ->orWhereHas('sizes', fn (Builder $sizeQuery) => $sizeQuery->whereNotNull('product_size.discount'));
            })
            ->latest('id')
            ->limit(8)
            ->get();

        $recommendedProducts = $this->productQuery()
            ->orderByDesc('views')
            ->limit(8)
            ->get();

        return view('storefront.home', [
            // Elanlar bölməsi: server boş qayıdarsa zolaq görünmür, yəni bu
            // bölmə olmayan qurulumda ana səhifə dəyişmir.
            'listingCards' => app(\App\Http\Controllers\Web\ListingWebController::class)->homeStrip(),
            'meta' => $this->meta(
                'Teymur Store - Ev, mətbəx və gündəlik məhsullar',
                'Teymur Store mobil tətbiqindəki endirimli məhsullara, kateqoriyalara və yeniliklərə veb üzərindən baxın.'
            ),
            'banners' => $this->banners(),
            'categories' => $this->categoryList(10),
            'featuredProducts' => $featuredProducts,
            'recommendedProducts' => $recommendedProducts,
            'settings' => $this->settings(),
            'downloadLinks' => $this->downloadLinks(),
        ]);
    }

    public function products(Request $request): View
    {
        $search = strip_tags(trim((string) $request->query('search', '')));
        $categoryId = $request->filled('category') ? $request->integer('category') : null;

        $products = $this->productQuery()
            ->when($categoryId, function (Builder $query) use ($categoryId): void {
                $categoryIds = $this->categoryAndChildrenIds($categoryId);
                $query->whereIn('category_id', $categoryIds);
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $escaped = mb_substr($search, 0, 100);
                $escaped = addcslashes($escaped, '%_\\');
                $query->where(function (Builder $inner) use ($escaped): void {
                    foreach (['az', 'en', 'ru', 'tr'] as $locale) {
                        $inner->orWhereRaw('title->>? ilike ?', [$locale, "%{$escaped}%"]);
                    }
                    $inner->orWhere('sku', 'ilike', "%{$escaped}%");
                });
            })
            ->latest('id')
            ->paginate(16)
            ->withQueryString();

        $category = $categoryId
            ? Category::query()->find($categoryId)
            : null;

        $title = $category
            ? Str::ucfirst($this->localized($category->name)) . ' - Teymur Store'
            : 'Məhsullar - Teymur Store';

        return view('storefront.products', [
            'meta' => $this->meta($title, 'Teymur Store məhsullarına baxın və sifariş üçün mobil tətbiqi yükləyin.'),
            'products' => $products,
            'categories' => $this->categoryList(18),
            'activeCategory' => $category,
            'search' => $search,
            'settings' => $this->settings(),
            'downloadLinks' => $this->downloadLinks(),
        ]);
    }

    public function product(Product $product): View
    {
        abort_unless((bool) $product->is_active, 404);

        $product->incrementQuietly('views');

        $product->load(['images', 'videos', 'colors', 'sizes', 'category', 'brand'])
            ->loadAvg('reviews', 'rate')
            ->loadCount('reviews');

        $relatedProducts = $this->productQuery()
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        $title = Str::ucfirst($this->localized($product->title)) . ' - Teymur Store';
        $description = Str::limit(strip_tags($this->localized($product->description)), 155);

        return view('storefront.product', [
            'meta' => $this->meta($title, $description, $this->productImage($product)),
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'settings' => $this->settings(),
            'downloadLinks' => $this->downloadLinks(),
        ]);
    }

    public function categories(): View
    {
        return view('storefront.categories', [
            'meta' => $this->meta('Kateqoriyalar - Teymur Store', 'Teymur Store kateqoriyalarını kəşf edin.'),
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->withCount(['products' => fn (Builder $q) => $q->where('is_active', true)])
                ->orderByDesc('sort_order')
                ->get(),
            'settings' => $this->settings(),
            'downloadLinks' => $this->downloadLinks(),
        ]);
    }

    public function about(): View
    {
        return view('storefront.page', [
            'meta' => $this->meta('Haqqımızda - Teymur Store', 'Teymur Store haqqında məlumat və mobil tətbiq keçidləri.'),
            'title' => 'Haqqımızda',
            'eyebrow' => 'Teymur Store',
            'content' => $this->legalHtml('about') ?: $this->fallbackAbout(),
            'settings' => $this->settings(),
            'downloadLinks' => $this->downloadLinks(),
        ]);
    }

    public function terms(): View
    {
        return view('storefront.page', [
            'meta' => $this->meta('Qaydalar və şərtlər - Teymur Store', 'Teymur Store istifadə qaydaları və məhsul siyasəti.'),
            'title' => 'Qaydalar və şərtlər',
            'eyebrow' => 'Məlumat',
            'content' => $this->legalHtml('main_page') ?: '<p>Qaydalar mobil tətbiqdə yenilənən formada burada göstərilir.</p>',
            'settings' => $this->settings(),
            'downloadLinks' => $this->downloadLinks(),
        ]);
    }

    public function contact(): View
    {
        return view('storefront.contact', [
            'meta' => $this->meta('Əlaqə - Teymur Store', 'Teymur Store ilə əlaqə, ünvan və sosial şəbəkə keçidləri.'),
            'settings' => $this->settings(),
            'downloadLinks' => $this->downloadLinks(),
            'faqs' => Faq::query()->latest('id')->limit(6)->get(),
        ]);
    }

    public function robots()
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return Response::make(
            "User-agent: *\nAllow: /\nSitemap: {$baseUrl}/sitemap.xml\n",
            200,
            ['Content-Type' => 'text/plain']
        );
    }

    public function sitemap()
    {
        $urls = Cache::remember('storefront:sitemap_urls', 3600, function () {
            $urls = collect([
                route('storefront.home'),
                route('storefront.products'),
                route('storefront.categories'),
                route('storefront.about'),
                route('storefront.terms'),
                route('storefront.contact'),
            ]);

            Product::query()->publiclyAvailable()
                ->where('is_active', true)
                ->latest('id')
                ->limit(500)
                ->pluck('id')
                ->each(fn ($id) => $urls->push(route('storefront.product', $id)));

            return $urls;
        });

        $xml = view('storefront.sitemap', ['urls' => $urls])->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    // ─── Private helpers ───

    private function productQuery(): Builder
    {
        return Product::query()->publiclyAvailable()
            ->where('is_active', true)
            ->with(['images', 'colors', 'sizes', 'category', 'brand'])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews');
    }

    private function banners(): Collection
    {
        return Cache::remember('storefront:banners', 300, function () {
            return Banner::query()->latest('id')->limit(6)->get();
        });
    }

    private function categoryList(int $limit): Collection
    {
        return Cache::remember("storefront:categories:{$limit}", 300, function () use ($limit) {
            return Category::query()
                ->whereNull('parent_id')
                ->withCount(['products' => fn (Builder $q) => $q->where('is_active', true)])
                ->orderByDesc('sort_order')
                ->limit($limit)
                ->get();
        });
    }

    private function settings(): ?Setting
    {
        return Cache::remember('storefront:settings', 600, function () {
            return Setting::query()->first();
        });
    }

    private function categoryAndChildrenIds(int $categoryId, int $depth = 0): array
    {
        if ($depth > 5) {
            return [$categoryId];
        }

        $ids = [$categoryId];
        $children = Category::query()->where('parent_id', $categoryId)->pluck('id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->categoryAndChildrenIds((int) $childId, $depth + 1));
        }

        return array_values(array_unique($ids));
    }

    private function legalHtml(string $type): ?string
    {
        $allowed = ['about', 'main_page'];
        if (!in_array($type, $allowed, true)) {
            return null;
        }

        $record = LegalTerm::query()->where('type', $type)->first();

        if (!$record) {
            return null;
        }

        return $this->cleanHtml($this->localized($record->html));
    }

    private function localized(mixed $value): string
    {
        if (is_array($value)) {
            return (string) ($value[app()->getLocale()] ?? $value['az'] ?? $value['en'] ?? reset($value) ?: '');
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->localized($decoded);
            }
        }

        return (string) $value;
    }

    private function cleanHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/javascript\s*:/i', '', $html) ?? '';
        $html = preg_replace('/data\s*:/i', '', $html) ?? '';
        $html = preg_replace('/vbscript\s*:/i', '', $html) ?? '';
        $html = preg_replace('/<meta[^>]*>/i', '', $html) ?? '';
        $html = preg_replace('/<link[^>]*>/i', '', $html) ?? '';
        $html = preg_replace('/<base[^>]*>/i', '', $html) ?? '';

        return $html;
    }

    private function fallbackAbout(): string
    {
        return '<p>Teymur Store gündəlik istifadə, ev, mətbəx, geyim və fərqli kateqoriyalarda seçilmiş məhsulları bir araya gətirir. Sifariş və tam funksionallıq mobil tətbiq üzərindən aparılır.</p>';
    }

    private function downloadLinks(): array
    {
        return [
            'ios' => config('app.app_store_url', '#'),
            'android' => config('app.play_store_url', '#'),
        ];
    }

    private function meta(string $title, string $description, ?string $image = null): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'image' => $image ?: asset('notification_icon.jpg'),
            'canonical' => url()->current(),
        ];
    }

    private function productImage(Product $product): ?string
    {
        return $product->images->first()?->image_path;
    }
}
