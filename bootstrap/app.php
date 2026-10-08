<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\SetLocaleFromHeader;
use App\Http\Middleware\TrustProxies;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            SetLocaleFromHeader::class,
        ]);
        $middleware->api(append: [\App\Http\Middleware\CachePublicApi::class]);
        $middleware->append([TrustProxies::class]);

        // Unauthenticated users are sent to the storefront admin login.
        $middleware->redirectGuestsTo(fn (Request $request): string => route('admin.login'));

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'admin.auth' => AdminAuthenticate::class,
            'subscribed' => EnsureSubscriptionActive::class,
            'feature' => EnsureFeatureEnabled::class,
            'admin.menu' => \App\Http\Middleware\EnforceAdminMenuAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => __('Unauthenticated'),
                ], 401);
            }
        });
    })->create();
