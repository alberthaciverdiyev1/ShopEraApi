<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin panel (Blade + htmx)
|--------------------------------------------------------------------------
|
| Loaded from bootstrap/app.php under the /admin prefix with the "web"
| middleware group (session + CSRF) and the admin.* route-name prefix.
|
*/

Route::middleware('guest:admin')->group(function () {
    Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Admin\AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['admin.auth', 'subscribed'])->group(function () {
    Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');

    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    /*
     * Registers the index/create/store/edit/update/destroy set for a simple
     * resource controller. Keeps this file a table of contents rather than a
     * wall of near-identical lines.
     */
    $resource = function (string $name, string $controller, ?string $feature = null): void {
        $routes = [
            Route::get($name, [$controller, 'index'])->name($name.'.index'),
            Route::get($name.'/create', [$controller, 'create'])->name($name.'.create'),
            Route::post($name, [$controller, 'store'])->name($name.'.store'),
            Route::get($name.'/{id}/edit', [$controller, 'edit'])->name($name.'.edit'),
            Route::put($name.'/{id}', [$controller, 'update'])->name($name.'.update'),
            Route::delete($name.'/{id}', [$controller, 'destroy'])->name($name.'.destroy'),
        ];

        if ($feature) {
            foreach ($routes as $route) {
                $route->middleware('feature:'.$feature);
            }
        }
    };

    // Catalogue
    Route::middleware('feature:products')->group(function () {
        Route::get('products', [Admin\ProductController::class, 'index'])->name('products.index');
        Route::get('products/create', [Admin\ProductController::class, 'create'])->name('products.create');
        Route::post('products', [Admin\ProductController::class, 'store'])->name('products.store');
        Route::get('products/{id}/edit', [Admin\ProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{id}', [Admin\ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{id}', [Admin\ProductController::class, 'destroy'])->name('products.destroy');
        Route::get('products/{id}', [Admin\ProductController::class, 'show'])->name('products.show');
        Route::delete('products/images/{imageId}', [Admin\ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::delete('products/videos/{id}', [Admin\ProductController::class, 'destroyVideo'])->name('products.videos.destroy');
    });
    Route::middleware('feature:bulk_price_update')->group(function () {
        Route::get('product-prices', [Admin\ProductController::class, 'prices'])->name('products.prices');
        Route::post('product-prices', [Admin\ProductController::class, 'applyPrices'])->name('products.prices.apply');
    });

    Route::get('categories/children', [Admin\CategoryController::class, 'children'])->name('categories.children');
    $resource('categories', Admin\CategoryController::class, 'categories');
    $resource('brands', Admin\BrandController::class, 'brands');
    $resource('colors', Admin\ColorController::class, 'colors');
    $resource('sizes', Admin\SizeController::class, 'sizes');
    Route::get('filters/form', [Admin\FilterController::class, 'form'])->middleware('feature:product_filters')->name('filters.form');
    $resource('filters', Admin\FilterController::class, 'product_filters');

    // Orders
    Route::middleware('feature:orders')->group(function () {
        Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{id}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{id}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::delete('orders/{id}', [Admin\OrderController::class, 'destroy'])->name('orders.destroy');
    });

    // Users
    Route::middleware('feature:users')->group(function () {
        Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('users/{id}', [Admin\UserController::class, 'show'])->name('users.show');
        Route::put('users/{id}/status', [Admin\UserController::class, 'updateStatus'])->name('users.status');
        Route::put('users/{id}/roles', [Admin\UserController::class, 'updateRoles'])->name('users.roles');
        Route::put('users/{id}/password', [Admin\UserController::class, 'changePassword'])->name('users.password');
        Route::delete('users/{id}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
    });

    // Reviews
    Route::middleware('feature:reviews')->group(function () {
        Route::get('reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::put('reviews/{id}/status', [Admin\ReviewController::class, 'updateStatus'])->name('reviews.status');
        Route::put('reviews/{id}/featured', [Admin\ReviewController::class, 'toggleFeatured'])->name('reviews.featured');
        Route::delete('reviews/{id}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');
    });

    // Content
    $resource('stories', Admin\StoryController::class, 'stories');
    $resource('blogs', Admin\BlogController::class, 'blog');
    $resource('banners', Admin\BannerController::class, 'banners');
    $resource('popups', Admin\PopupController::class, 'popups');
    $resource('faqs', Admin\FaqController::class, 'faq');
    $resource('promocodes', Admin\PromoCodeController::class, 'promo_codes');
    $resource('contact-messages', Admin\ContactMessageController::class);

    Route::middleware('feature:legal_terms')->group(function () {
        Route::get('legal-terms', [Admin\LegalTermController::class, 'index'])->name('legal-terms.index');
        Route::get('legal-terms/{id}/edit', [Admin\LegalTermController::class, 'edit'])->name('legal-terms.edit');
        Route::put('legal-terms/{id}', [Admin\LegalTermController::class, 'update'])->name('legal-terms.update');
    });

    // Delivery
    $resource('delivery-prices', Admin\DeliveryPriceController::class, 'delivery_prices');
    $resource('delivery-cities', Admin\DeliveryCityController::class, 'delivery_cities');
    $resource('pickup-points', Admin\PickupPointController::class, 'pickup_points');

    Route::middleware('feature:delivery_info')->group(function () {
        Route::get('delivery-infos', [Admin\DeliveryInfoController::class, 'index'])->name('delivery-infos.index');
        Route::get('delivery-infos/{id}/edit', [Admin\DeliveryInfoController::class, 'edit'])->name('delivery-infos.edit');
        Route::put('delivery-infos/{id}', [Admin\DeliveryInfoController::class, 'update'])->name('delivery-infos.update');
    });

    // Chat
    Route::middleware('feature:chat')->group(function () {
        Route::get('chat', [Admin\ChatController::class, 'index'])->name('chat.index');
        Route::get('chat/{id}', [Admin\ChatController::class, 'show'])->name('chat.show');
        Route::post('chat/{id}/send', [Admin\ChatController::class, 'send'])->name('chat.send');
        Route::delete('chat/message/{id}', [Admin\ChatController::class, 'destroyMessage'])->name('chat.message.destroy');
        Route::delete('chat/{id}', [Admin\ChatController::class, 'destroyConversation'])->name('chat.destroy');
    });
    $resource('auto-replies', Admin\AutoReplyController::class, 'auto_reply');

    // Staff
    Route::get('team', [Admin\TeamController::class, 'index'])->middleware('feature:users')->name('team.index');

    // Notifications
    Route::middleware('feature:push_notifications')->group(function () {
        Route::get('notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/create', [Admin\NotificationController::class, 'create'])->name('notifications.create');
        Route::post('notifications', [Admin\NotificationController::class, 'store'])->name('notifications.store');
        Route::delete('notifications/{id}', [Admin\NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    // Roles & permissions
    Route::middleware('feature:roles_permissions')->group(function () {
        Route::get('roles', [Admin\RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [Admin\RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [Admin\RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{id}/edit', [Admin\RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{id}', [Admin\RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{id}', [Admin\RoleController::class, 'destroy'])->name('roles.destroy');
    });

    // System
    Route::middleware('feature:theme_colors')->group(function () {
        Route::get('theme', [Admin\ThemeController::class, 'index'])->name('theme.index');
        Route::put('theme', [Admin\ThemeController::class, 'update'])->name('theme.update');
        Route::post('theme/select', [Admin\ThemeController::class, 'select'])->name('theme.select');
    });

    Route::middleware('feature:settings')->group(function () {
        Route::get('settings', [Admin\SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
    });
    Route::middleware('feature:referrals')->group(function () {
        Route::get('referral', [Admin\ReferralSettingController::class, 'index'])->name('referral.index');
        Route::put('referral', [Admin\ReferralSettingController::class, 'update'])->name('referral.update');
    });
});
