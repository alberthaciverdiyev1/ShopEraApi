<?php

namespace App\Services\Storefront;

use Modules\User\Services\FavoriteService;

/**
 * The signed-in user's wishlist, backed by the same favourites service the
 * mobile API uses.
 */
class WishlistService
{
    public function __construct(private readonly FavoriteService $favorites) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function products(): array
    {
        return $this->favorites->list()->getData(true)['data'] ?? [];
    }

    public function remove(int $productId): void
    {
        $this->favorites->delete($productId);
    }
}
