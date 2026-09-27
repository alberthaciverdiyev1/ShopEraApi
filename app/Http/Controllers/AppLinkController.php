<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class AppLinkController extends Controller
{
    public function assetLinks(): JsonResponse
    {
        $fingerprints = config('services.app_links.android_sha256_cert_fingerprints', []);

        if (empty($fingerprints)) {
            return response()->json([]);
        }

        return response()->json([
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => config('services.app_links.android_package_name', 'com.app.teymur'),
                    'sha256_cert_fingerprints' => $fingerprints,
                ],
            ],
        ]);
    }

    public function appleAssociation(): JsonResponse
    {
        return response()->json([
            'applinks' => [
                'apps' => [],
                'details' => collect(config('services.app_links.ios_app_ids', []))
                    ->filter()
                    ->map(fn($appId) => [
                        'appID' => $appId,
                        // Product pages and shared live streams. Adding a path
                        // here is only half the job — iOS caches this file, so a
                        // build already on a device keeps the old list until it
                        // is reinstalled or updated.
                        'paths' => ['/mehsullar/*', '/canli/*', '/elan/*'],
                        'components' => [
                            ['/' => '/mehsullar/*'],
                            ['/' => '/canli/*'],
                            ['/' => '/elan/*'],
                        ],
                    ])
                    ->values()
                    ->all(),
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
