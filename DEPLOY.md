# ShopEra — Host kurulumu (Docker'sız)

Proje (API + Svelte) ve Manager host üzerinde çalışır. Önkoşullar: PHP 8.3-FPM,
Node 20+, PostgreSQL, nginx. Domain'ler Cloudflare arkasında; TLS Cloudflare'de
biter, origin düz HTTP'dir.

## 0. Veritabanı — paylaşımlı `global-postgres`

Uygulama, paylaşımlı `global-postgres` konteynerini kullanır (host portu `5432`):

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1        # global-postgres host'a 5432 olarak açık
DB_PORT=5432
DB_DATABASE=shopera      # merkezi DB; tenant DB'leri de bu sunucuda açılır
DB_USERNAME=<global-postgres kullanıcısı>
DB_PASSWORD=<global-postgres şifresi>
```

> **Eklentiler:** Bu sunucuda `vector` **var**, ancak `unaccent` ve `pg_trgm` **yok**.
> Uygulama buna göre davranır: migration'lar hata vermez, arama `unaccent`/benzerlik
> olmadan düz `ILIKE`'a düşer (akıllı arama/AI benzerlik devre dışı). Tam arama için
> `global-postgres` imajını `pgvector/pgvector:pg17` (contrib dâhil) yapın ya da
> sunucuya `unaccent` + `pg_trgm` eklentilerini kurun. Veri volume'ü korunur.

## 1. ShopEra API (Laravel)

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# .env: yukarıdaki DB_*, TENANCY_ENABLED=true, MANAGER_*, REDIS_* (varsa)
php artisan migrate --force
php artisan db:seed --force            # merkezi/tek-kurulum
php artisan storage:link
sudo APP_DIR=/var/www/ShopEra APP_USER=www-data APP_GROUP=www-data bash deploy/scripts/fix-permissions.sh
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### nginx (catch-all — çok-tenant için şart)
Hazır template: `deploy/nginx/shopera-catch-all.conf`. Bu blok default vhost
olmalıdır; Cloudflare'de əlavə etdiyiniz hər subdomain/custom domain eyni origin'ə
gəlsə, nginx dəyişmədən ShopEra tenantı `Host` header-dən tapacaq.

```nginx
server {
    listen 80 default_server;
    server_name _;                     # her Host'u karşıla; tenant Host'tan çözülür
    root /var/www/ShopEra/public;
    index index.php;
    client_max_body_size 100m;

    location / { try_files $uri /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

### Kuyruk worker (supervisor)
```ini
[program:shopera-worker]
command=php /var/www/ShopEra/artisan queue:work --sleep=1 --tries=1 --timeout=600
autostart=true
autorestart=true
numprocs=1
user=www-data
```

### Scheduler (cron)
```cron
* * * * * www-data cd /var/www/ShopEra && php artisan schedule:run >> /dev/null 2>&1
```

## 2. Svelte storefront (SvelteKit adapter-node)

```bash
cd /var/www/ShopEra/resources/frontend
npm ci && npm run build
```

systemd servisi:
```ini
[Unit]
Description=ShopEra storefront
After=network.target

[Service]
WorkingDirectory=/var/www/ShopEra/resources/frontend
Environment=PORT=3000
Environment=HOST=127.0.0.1
Environment=HOST_HEADER=x-forwarded-host
Environment=PROTOCOL_HEADER=x-forwarded-proto
Environment=INTERNAL_API_URL=http://127.0.0.1/api
ExecStart=/usr/bin/node build
Restart=always
User=www-data

[Install]
WantedBy=multi-user.target
```

nginx'te storefront'u köke, `/api` ve `/admin`'i Laravel'e yönlendirin; SSR'ın
`INTERNAL_API_URL`'e giderken tenant Host'unu taşıması `hooks.server.ts` ile yapılır.

## 3. Manager (Manager.Shopera)

Ayrı bir Laravel kurulumu (aynı host veya ayrı sunucu):

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
# .env: DB_DATABASE=manager_shopera, MANAGER_* , CLOUDFLARE_* (aşağıda)
php artisan migrate --force && php artisan db:seed --force
sudo APP_DIR=/var/www/Manager.Shopera APP_USER=www-data APP_GROUP=www-data bash deploy/scripts/fix-permissions.sh
php artisan config:cache && php artisan route:cache
```

- nginx vhost'u Manager alan adına bağlayın (admin paneli buradan çalışır).
- Scheduler: `billing:remind` için `schedule:run` cron'u (yukarıdaki gibi).

### Cloudflare ortam değişkenleri (Manager)
```
CLOUDFLARE_ENABLED=true
CLOUDFLARE_API_TOKEN=<Zone:Read + DNS:Edit>
CLOUDFLARE_BASE_DOMAIN=shopera.az
CLOUDFLARE_DNS_TARGET=<origin IP | <tunnel>.cfargotunnel.com>
CLOUDFLARE_DNS_TYPE=A            # tunnel kullanıyorsanız CNAME
CLOUDFLARE_PROXIED=true
CLOUDFLARE_WILDCARD_SUBDOMAINS=true
# CLOUDFLARE_ZONE_ID=<base zone id>   # opsiyonel
```

## Notlar
- **TLS/DNS:** Subdomainler `*.base_domain` wildcard kaydıyla; custom domainler
  Manager `CloudflareDns` servisiyle otomatik (proxied) yönetilir.
- **Güvenlik:** Laravel `TRUSTED_PROXIES` üretimde Cloudflare/proxy IP'leri olmalı
  (X-Forwarded-Host'a güvenildiği için `*` bırakılmaz).
