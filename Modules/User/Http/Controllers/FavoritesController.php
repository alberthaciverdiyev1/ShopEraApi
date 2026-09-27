<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Services\FavoriteService;

class FavoritesController extends Controller
{
    private FavoriteService $service;

    public function __construct(FavoriteService $service)
    {
        $this->service = $service;
    }

    /**
     * List user favorites
     */
    public function list(Request $request)
    {
        return $this->service->list();
    }

    /**
     * Add product to favorites
     */
    public function add(int $productId)
    {
        return $this->service->add($productId);
    }

    /**
     * Remove product from favorites
     */
    public function delete(int $productId)
    {
        return $this->service->delete($productId);
    }
}
