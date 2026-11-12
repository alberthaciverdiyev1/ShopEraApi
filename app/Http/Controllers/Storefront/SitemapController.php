<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\CatalogService;
use Illuminate\Http\Response;
use Modules\Product\Entities\Product;

/**
 * robots.txt and sitemap.xml for the Blade storefront, so search engines get
 * the same URLs the old SvelteKit build used to expose.
 */
class SitemapController extends Controller
{
    private const STATIC_PAGES = ['/', '/shop', '/contact', '/about', '/faq', '/blog'];

    public function robots(): Response
    {
        $body = "User-agent: *\nAllow: /\n\nSitemap: ".url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function sitemap(CatalogService $catalog): Response
    {
        $origin = rtrim(url('/'), '/');
        $locs = array_map(fn (string $path) => $origin.$path, self::STATIC_PAGES);

        foreach ($this->flatten($catalog->categories()) as $category) {
            if (! empty($category['id'])) {
                $locs[] = $origin.'/shop?category='.$category['id'];
            }
        }

        Product::query()
            ->publiclyAvailable()
            ->whereNotNull('slug')
            ->orderBy('id')
            ->pluck('slug')
            ->each(function (string $slug) use ($origin, &$locs) {
                $locs[] = $origin.'/elan/'.$slug;
            });

        $urls = collect($locs)
            ->unique()
            ->map(fn (string $loc) => '  <url><loc>'.htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>')
            ->implode("\n");

        $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$urls."\n"
            .'</urlset>';

        return response($body, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $categories
     * @return array<int, array<string, mixed>>
     */
    private function flatten(iterable $categories): array
    {
        $flat = [];

        foreach ($categories as $category) {
            $flat[] = $category;

            foreach ($this->flatten($category['children'] ?? []) as $child) {
                $flat[] = $child;
            }
        }

        return $flat;
    }
}
