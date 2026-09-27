<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\HelpAndPolicy\Services\FaqService;
use Modules\Setting\Services\SettingService;

class HomeController extends Controller
{
    private FaqService $faqService;
    private SettingService $settingService;

    function __construct(FaqService $faqService,SettingService $settingService)
    {
        $this->faqService = $faqService;
        $this->settingService = $settingService;
    }

    public function index(Request $request)
    {
        $faqsResponse = $this->faqService->getAll($request);
        $faqs= $faqsResponse->getData(true)['data'];

        $settingResponse = $this->settingService->list();
        $setting= $settingResponse->getData(true)['data'][0];
        return view('home', compact('faqs','setting'));
    }

}
