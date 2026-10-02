<?php

namespace App\Http\Controllers\Api;

use App\Support\TenantDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Receives signed events from Manager.ShopEra:
 *   - tenant.provision → create + migrate the owner's own database
 *   - anything else    → refresh entitlements/theme (manager:sync)
 */
class ManagerWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = (string) config('services.manager.webhook_secret');

        if ($secret === '') {
            return response()->json(['ok' => false, 'message' => 'Webhook not configured.'], 500);
        }

        $signature = (string) $request->header('X-Manager-Signature');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            return response()->json(['ok' => false, 'message' => 'Invalid signature.'], 401);
        }

        $event = (string) ($request->header('X-Manager-Event') ?: $request->input('event', 'entitlements.updated'));

        if ($event === 'tenant.provision') {
            $hosts = (array) $request->input('hosts', []);
            $database = (string) $request->input('database', '');
            $storageRoot = (string) $request->input('storage_root', '');
            $provisioned = [];

            $admin = [];
            if ($request->input('admin_email')) {
                $admin = [
                    '--admin-email' => (string) $request->input('admin_email'),
                    '--admin-password' => (string) $request->input('admin_password'),
                    '--admin-name' => (string) $request->input('admin_name', ''),
                    '--admin-phone' => (string) $request->input('admin_phone', ''),
                ];
            }

            foreach (array_filter($hosts) as $host) {
                $args = ['host' => $host];
                if ($database !== '') {
                    $args['--database'] = $database;
                }
                if ($storageRoot !== '') {
                    $args['--storage-root'] = $storageRoot;
                }

                Artisan::call('tenant:provision', array_merge($args, $admin));
                $provisioned[] = $host;

                // Always clear the existence flag (explicit or derived name).
                TenantDatabase::forgetExistence($database !== '' ? $database : TenantDatabase::nameFor($host));
            }

            // The provisioner switched the default connection to the new tenant
            // database; restore the central connection before syncing the map.
            DB::setDefaultConnection(TenantDatabase::centralConnection());
            DB::purge('tenant');

            // Refresh the host → database map so ResolveTenant knows the new tenant.
            Artisan::call('manager:sync');

            return response()->json(['ok' => true, 'event' => $event, 'provisioned' => $provisioned]);
        }

        // Mirror the change into the affected tenant database only.
        $host = (string) (
            $request->input('host')
            ?: $request->input('domain')
            ?: collect((array) $request->input('hosts', []))->first()
            ?: $request->getHost()
        );

        Artisan::call('manager:sync', ['--host' => $host]);

        return response()->json(['ok' => true, 'event' => $event]);
    }
}
