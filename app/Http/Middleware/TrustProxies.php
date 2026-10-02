<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Symfony\Component\HttpFoundation\Request as RequestAlias;

class TrustProxies extends Middleware
{
    /**
     * Overridden by config/proxy.php (TRUSTED_PROXIES env). "*" trusts every
     * proxy, which is fine for local development but must be narrowed in
     * production (Cloudflare / reverse-proxy IPs).
     *
     * @var array<int,string>|string|null
     */
    protected $proxies;

    protected $headers =
        RequestAlias::HEADER_X_FORWARDED_FOR |
        RequestAlias::HEADER_X_FORWARDED_HOST |
        RequestAlias::HEADER_X_FORWARDED_PORT |
        RequestAlias::HEADER_X_FORWARDED_PROTO;

    public function __construct()
    {
        $trusted = config('proxy.trusted');

        $this->proxies = ! empty($trusted) ? $trusted : '*';
    }
}
