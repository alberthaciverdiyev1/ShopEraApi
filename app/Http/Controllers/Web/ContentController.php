<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Concerns\InteractsWithModuleControllers;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\HelpAndPolicy\Http\Controllers\FaqController;
use Modules\HelpAndPolicy\Http\Controllers\LegalTermController;
use Modules\Product\Http\Controllers\ReviewController;
use Modules\Setting\Http\Controllers\ContactController;
use Modules\Setting\Http\Requests\ContactRequest;

class ContentController extends Controller
{
    use InteractsWithModuleControllers;

    public function about(): View
    {
        app()->setLocale('az');

        return view('storefront.content.about', [
            'html' => $this->legalTerm('about'),
            'reviews' => array_slice(
                (array) $this->payload(app(ReviewController::class)->featured()),
                0,
                6
            ),
            'categories' => $this->storefrontCategories(),
        ]);
    }

    public function faq(): View
    {
        app()->setLocale('az');

        $payload = $this->payload(
            app(FaqController::class)->getAll($this->moduleHttpRequest())
        );

        $faqs = collect($payload ?? [])
            ->map(fn ($faq) => [
                'id' => (int) ($faq['id'] ?? 0),
                'title' => $this->localize($faq['title'] ?? null),
                'description' => $this->localize($faq['description'] ?? null),
                'type' => $faq['type'] ?? null,
            ])
            ->filter(fn ($faq) => $faq['title'] !== '')
            ->values()
            ->all();

        return view('storefront.content.faq', [
            'faqs' => $faqs,
            'categories' => $this->storefrontCategories(),
        ]);
    }

    public function privacy(): View
    {
        return $this->legal('main_page', 'Gizlilik siyasəti');
    }

    public function terms(): View
    {
        return $this->legal('register_page', 'İstifadə şərtləri');
    }

    public function contact(): View
    {
        app()->setLocale('az');

        return view('storefront.content.contact', [
            'contactInfo' => $this->payload(app(ContactController::class)->info()),
            'categories' => $this->storefrontCategories(),
        ]);
    }

    public function sendContact(ContactRequest $request): RedirectResponse
    {
        app(ContactController::class)->send($request);

        return redirect()
            ->route('storefront.contact')
            ->with('status', 'Mesajınız uğurla göndərildi. Tezliklə sizinlə əlaqə saxlanılacaq.');
    }

    private function legal(string $type, string $title): View
    {
        app()->setLocale('az');

        return view('storefront.content.legal', [
            'html' => $this->legalTerm($type),
            'pageTitle' => $title,
            'categories' => $this->storefrontCategories(),
        ]);
    }

    /**
     * The HTML body of a legal term (`about`, `privacy`, `terms`, …).
     */
    private function legalTerm(string $type): ?string
    {
        $payload = $this->payload(
            app(LegalTermController::class)->getAll($this->moduleHttpRequest(['type' => $type]))
        );

        $term = $payload[0] ?? null;

        return $term ? $this->localize($term['html'] ?? null) : null;
    }

    /**
     * Translatable values arrive either as a string or as a locale map.
     */
    private function localize(mixed $value, string $locale = 'az'): string
    {
        if (is_array($value)) {
            return (string) ($value[$locale] ?? reset($value) ?: '');
        }

        return (string) ($value ?? '');
    }
}
