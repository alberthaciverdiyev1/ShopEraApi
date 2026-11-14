<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Setting\Services\SocialLinkService;

class SocialLinkController extends Controller
{
    public function __construct(private readonly SocialLinkService $service) {}

    /** Social icons for the storefront footer. Public: the footer is on every page. */
    public function index()
    {
        return responseHelper(__('Social links retrieved successfully.'), 200, [
            'links' => $this->service->links(),
            'platforms' => SocialLinkService::PLATFORMS,
        ]);
    }
}
