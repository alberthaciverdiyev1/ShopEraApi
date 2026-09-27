<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Listing\Http\Entities\Listing;
use Modules\Listing\Http\Entities\ListingSection;
use Modules\Listing\Http\Resources\ListingSnapshot;
use Modules\Listing\Http\Resources\ListingText;
use Modules\Listing\Services\ListingBadgeService;
use Modules\Listing\Services\ListingService;

/**
 * The classifieds on the website.
 *
 * The same ads, the same filters and the same card as the app - the query
 * comes from ListingService::browse(), so "make = BMW and year from 2015"
 * cannot mean one thing here and another on a phone.
 *
 * Every ad page carries a link the app claims through the association files,
 * so opening it on a phone with the app installed lands on the ad inside the
 * app, and on the page for everyone else.
 */
class ListingWebController extends Controller
{
    public function __construct(
        private readonly ListingService $listings,
        private readonly ListingBadgeService $badges,
    ) {
    }

    /**
     * The strip on the shop's own home page. Empty when the classifieds are
     * not set up, and the home page then looks exactly as it did.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function homeStrip(int $limit = 6)
    {
        return Listing::query()
            ->visible()
            ->with(['section', 'city', 'media'])
            ->orderByDesc('is_vip')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (Listing $listing) => $this->card($listing));
    }

    /** Every section, and the newest ads across all of them. */
    public function index(Request $request): View
    {
        $sections = $this->sections();

        $latest = Listing::query()
            ->visible()
            ->with(['section', 'city', 'media'])
            ->orderByDesc('is_vip')
            ->orderByDesc('published_at')
            ->limit(12)
            ->get()
            ->map(fn (Listing $listing) => $this->card($listing));

        return view('storefront.listings.index', [
            'meta' => $this->meta(
                'Elanlar - Teymur Store',
                'Nəqliyyat, daşınmaz əmlak və digər bölmələrdə elanlara baxın.',
            ),
            'sections' => $sections,
            'latest' => $latest,
        ]);
    }

    /** One section: the filters it declares and its ads. */
    public function section(Request $request, string $key): View
    {
        $section = ListingSection::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->with(['fields' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->firstOrFail();

        $ads = $this->listings->browse($request, $section)
            ->paginate(24)
            ->withQueryString();

        return view('storefront.listings.section', [
            'meta' => $this->meta(
                $section->name.' elanları - Teymur Store',
                $section->name.' bölməsindəki elanları axtarın və filtrləyin.',
            ),
            'sections' => $this->sections(),
            'section' => $section,
            'ads' => $ads,
            'cards' => $ads->getCollection()->map(fn (Listing $listing) => $this->card($listing)),
            // Only the fields the section marked for filtering, with their
            // choices already loaded - the page is rendered, not fetched.
            'filters' => $section->fields
                ->where('in_filter', true)
                // Asılı sahə (model markadan asılıdır) saytda süzgəcə
                // qoyulmur: seçimləri valideyn seçilənə qədər məlum deyil,
                // sərbəst mətn isə heç nə tapmır.
                ->whereNull('parent_id')
                ->map(fn ($field) => [
                    'field' => $field,
                    'options' => $field->usesOptions() && $field->parent_id === null
                        ? $field->options()->where('is_active', true)->orderBy('sort_order')->get()
                        : collect(),
                ])
                ->values(),
            'chosen' => (array) $request->input('f', []),
        ]);
    }

    /** One ad. */
    public function show(Request $request, int $id): View
    {
        $listing = Listing::with(['section', 'city', 'media', 'user'])
            ->visible()
            ->findOrFail($id);

        // Not through the model: counting a view should not rewrite
        // updated_at or fire model events.
        Listing::whereKey($listing->id)->update(['views' => DB::raw('views + 1')]);

        $fields = (new ListingSnapshot($listing->attribute_values))->all();
        $video = null;
        $point = null;

        foreach ($fields as $field) {
            if ($field['type'] === 'youtube' && is_string($field['value'] ?? null)) {
                $video = $field['value'];
            }
            if ($field['type'] === 'location' && is_array($field['value'] ?? null)) {
                $point = $field['value'];
            }
        }

        $similar = Listing::query()
            ->visible()
            ->where('section_id', $listing->section_id)
            ->whereKeyNot($listing->id)
            ->with(['section', 'city', 'media'])
            ->orderByDesc('is_vip')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get()
            ->map(fn (Listing $item) => $this->card($item));

        return view('storefront.listings.show', [
            'meta' => $this->meta(
                $listing->title.' - Teymur Store',
                mb_substr(strip_tags((string) $listing->description), 0, 160) ?: $listing->title,
                $listing->media->firstWhere('type', 'image')?->url(),
            ),
            'listing' => $listing,
            'priceLabel' => ListingText::price($listing->price === null ? null : (float) $listing->price, $listing->currency),
            'publishedLabel' => ListingText::moment($listing->published_at ?? $listing->created_at),
            'expiresLabel' => ListingText::date($listing->expires_at),
            'summary' => ListingText::summary((new ListingSnapshot($listing->attribute_values))->cardFields()),
            'badges' => $this->badges->present($listing),
            // The video and the point are drawn as their own blocks, so they
            // are taken out of the table the way the app takes them out.
            'fields' => array_values(array_filter(
                $fields,
                fn (array $field) => ! in_array($field['type'], ['youtube', 'location'], true),
            )),
            'video' => $video,
            'point' => $point,
            'map' => config('listing.map'),
            'photos' => $listing->media->where('type', 'image')->map(fn ($item) => $item->url())->values(),
            'similar' => $similar,
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, ListingSection> */
    private function sections()
    {
        return ListingSection::query()
            ->where('is_active', true)
            ->withCount(['listings' => fn ($query) => $query->visible()])
            ->orderBy('sort_order')
            ->get();
    }

    /** One ad as a card: exactly what the app's card prints. */
    private function card(Listing $listing): array
    {
        $cardFields = (new ListingSnapshot($listing->attribute_values))->cardFields();

        return [
            'id' => $listing->id,
            'url' => route('storefront.listing', ['id' => $listing->id]),
            'title' => $listing->title,
            'price' => ListingText::price($listing->price === null ? null : (float) $listing->price, $listing->currency),
            'summary' => array_slice(ListingText::summary($cardFields), 0, 3),
            'city' => $listing->city?->name,
            'time' => ListingText::moment($listing->published_at ?? $listing->created_at),
            'cover' => $listing->media->firstWhere('type', 'image')?->url(),
            'badges' => $this->badges->present($listing),
        ];
    }

    /** @return array<string, string|null> */
    private function meta(string $title, string $description, ?string $image = null): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'image' => $image ?: asset('notification_icon.jpg'),
            'canonical' => url()->current(),
        ];
    }
}
