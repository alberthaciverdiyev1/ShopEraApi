# Snaker — Host kurulumu (Docker'sız)

Proje (API + Svelte) tek host üzerinde çalışır. Önkoşullar: PHP 8.3-FPM,
Node 20+, PostgreSQL, nginx. Domain Cloudflare arkasında; TLS Cloudflare'de
biter, origin düz HTTP'dir.

## 0. Veritabanı — paylaşımlı `global-postgres`

Uygulama, paylaşımlı `global-postgres` konteynerini kullanır (host portu `5432`):

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1        # global-postgres host'a 5432 olarak açık
DB_PORT=5432
DB_DATABASE=snaker      # tek uygulama DB'si
DB_USERNAME=<global-postgres kullanıcısı>
DB_PASSWORD=<global-postgres şifresi>
```

> **Eklentiler:** Bu sunucuda `vector` **var**, ancak `unaccent` ve `pg_trgm` **yok**.
> Uygulama buna göre davranır: migration'lar hata vermez, arama `unaccent`/benzerlik
> olmadan düz `ILIKE`'a düşer (akıllı arama/AI benzerlik devre dışı). Tam arama için
> `global-postgres` imajını `pgvector/pgvector:pg17` (contrib dâhil) yapın ya da
> sunucuya `unaccent` + `pg_trgm` eklentilerini kurun. Veri volume'ü korunur.

## 1. Snaker API (Laravel)

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# .env: yukarıdaki DB_*, REDIS_* (varsa)
php artisan migrate --force
php artisan db:seed --force            # tek kurulum
php artisan storage:link
sudo APP_DIR=/var/www/Snaker APP_USER=www-data APP_GROUP=www-data bash deploy/scripts/fix-permissions.sh
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### nginx (catch-all)
Hazır template: `deploy/nginx/snaker-catch-all.conf`. Bu blok default vhost
olmalıdır; Cloudflare'de eklenen her custom domain aynı origin'e gelse, nginx
değişmeden karşılanır.

```nginx
server {
    listen 80 default_server;
    server_name _;                     # her Host'u karşıla (catch-all)
    root /var/www/Snaker/public;
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
[program:snaker-worker]
command=php /var/www/Snaker/artisan queue:work --sleep=1 --tries=1 --timeout=600
autostart=true
autorestart=true
numprocs=1
user=www-data
```

### Scheduler (cron)
```cron
* * * * * www-data cd /var/www/Snaker && php artisan schedule:run >> /dev/null 2>&1
```

## 2. Svelte storefront (SvelteKit adapter-node)

```bash
cd /var/www/Snaker/resources/frontend
npm ci && npm run build
```

systemd servisi:
```ini
[Unit]
Description=Snaker storefront
After=network.target

[Service]
WorkingDirectory=/var/www/Snaker/resources/frontend
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
`INTERNAL_API_URL`'e giderken public Host'u taşıması `hooks.server.ts` ile yapılır.

## Notlar
- **TLS/DNS:** TLS Cloudflare edge'de biter; custom domainler aynı origin'e
  yönlendirilir, nginx değişikliği gerekmez.
- **Güvenlik:** Laravel `TRUSTED_PROXIES` üretimde Cloudflare/proxy IP'leri olmalı
  (X-Forwarded-Host'a güvenildiği için `*` bırakılmaz).
