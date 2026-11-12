<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Services\Storefront\CatalogService;
use App\Services\Storefront\ContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly CatalogService $catalog,
    ) {}

    public function about(): View
    {
        app()->setLocale('az');

        return view('storefront.content.about', [
            'html' => $this->content->legalTerm('about'),
            'reviews' => $this->content->featuredReviews(),
            'categories' => $this->catalog->categories(),
        ]);
    }

    public function faq(): View
    {
        app()->setLocale('az');

        return view('storefront.content.faq', [
            'faqs' => $this->content->faqs(),
            'categories' => $this->catalog->categories(),
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
            'contactInfo' => $this->content->contactInfo(),
            'categories' => $this->catalog->categories(),
        ]);
    }

    public function sendContact(ContactRequest $request): RedirectResponse
    {
        $this->content->submitContact($request->validated(), $request->ip());

        return redirect()
            ->route('storefront.contact')
            ->with('status', 'Mesajınız uğurla göndərildi. Tezliklə sizinlə əlaqə saxlanılacaq.');
    }

    private function legal(string $type, string $title): View
    {
        app()->setLocale('az');

        return view('storefront.content.legal', [
            'html' => $this->content->legalTerm($type),
            'pageTitle' => $title,
            'categories' => $this->catalog->categories(),
        ]);
    }
}
