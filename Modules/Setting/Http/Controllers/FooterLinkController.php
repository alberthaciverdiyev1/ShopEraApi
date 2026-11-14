<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Setting\Services\FooterLinkService;

class FooterLinkController extends Controller
{
    public function __construct(private readonly FooterLinkService $service) {}

    /** Footer columns for the storefront. Public: the footer is on every page. */
    public function index()
    {
        return responseHelper(__('Footer links retrieved successfully.'), 200, [
            'groups' => $this->service->groups(),
            'sections' => FooterLinkService::SECTIONS,
        ]);
    }
}
