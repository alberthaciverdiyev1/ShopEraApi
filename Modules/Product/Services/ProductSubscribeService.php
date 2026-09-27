<?php

namespace Modules\Product\Services;

use Illuminate\Support\Facades\DB;
use Modules\Notification\Services\NotificationService;
use Modules\Product\Http\Entities\Product;
use Modules\User\Http\Entities\User;

class ProductSubscribeService
{
    /**
     * Subscribe
     */
    public function subscribe(User $user, int $productId): void
    {
        Product::query()->findOrFail($productId);

        $user->stockSubscriptions()->syncWithoutDetaching([
            $productId => [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }


    /**
     * Unsubscribe
     */
    public function unsubscribe(User $user, int $productId): void
    {
        Product::query()->findOrFail($productId);
        $user->stockSubscriptions()->detach($productId);
    }

    /**
     * Notify subscribers when product is back in stock
     */
    public function notifySubscribers(Product $product): void
    {
        if ($product->stock_count <= 0) {
            return;
        }

        DB::transaction(function () use ($product) {

            $subscribers = $product->stockSubscribers()
                ->wherePivotNull('notified_at')
                ->lockForUpdate()
                ->get();

            if ($subscribers->isEmpty()) {
                return;
            }

            // getTranslation, not $product->title['az']: the translatable
            // accessor hands back the active locale's string, so indexing it
            // by language read a single character instead of the title.
            $name = $product->getTranslation('title', 'az')
                ?: __('Product is back in stock');

            // main_image is not an attribute on Product; take the real one.
            $image = $product->images()->first()?->image_path;

            $notifications = app(NotificationService::class);

            foreach ($subscribers as $user) {
                // Stored for every subscriber, pushed to whoever has a device.
                // The old code skipped the whole notification when a user had
                // no token, so they never saw it in the app either.
                $notifications->add([
                    'title' => __('Product is back in stock'),
                    'body' => $name,
                    'user_id' => $user->id,
                    'image' => $image,
                    'data' => [
                        'type'       => 'product_back_in_stock',
                        'product_id' => (string) $product->id,
                    ],
                ]);
            }

//            $product->stockSubscribers()->updateExistingPivot(
//                $subscribers->pluck('id')->toArray(),
//                ['notified_at' => now()]
//            );
        });
    }
}
