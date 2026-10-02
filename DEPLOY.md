# Snaker — Host kurulumu (Docker'sız)

Proje (API + Svelte) ve Manager host üzerinde çalışır. Önkoşullar: PHP 8.3-FPM,
Node 20+, PostgreSQL, nginx. Domain'ler Cloudflare arkasında; TLS Cloudflare'de
biter, origin düz HTTP'dir.

## 0. Veritabanı — paylaşımlı `global-postgres`

Uygulama, paylaşımlı `global-postgres` konteynerini kullanır (host portu `5432`):

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1        # global-postgres host'a 5432 olarak açık
DB_PORT=5432
DB_DATABASE=snaker      # merkezi DB; tenant DB'leri de bu sunucuda açılır
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
# .env: yukarıdaki DB_*, TENANCY_ENABLED=true, MANAGER_*, REDIS_* (varsa)
php artisan migrate --force
php artisan db:seed --force            # merkezi/tek-kurulum
php artisan storage:link
sudo APP_DIR=/var/www/Snaker APP_USER=www-data APP_GROUP=www-data bash deploy/scripts/fix-permissions.sh
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### nginx (catch-all — çok-tenant için şart)
Hazır template: `deploy/nginx/snaker-catch-all.conf`. Bu blok default vhost
olmalıdır; Cloudflare'de əlavə etdiyiniz hər subdomain/custom domain eyni origin'ə
gəlsə, nginx dəyişmədən Snaker tenantı `Host` header-dən tapacaq.

```nginx
server {
    listen 80 default_server;
    server_name _;                     # her Host'u karşıla; tenant Host'tan çözülür
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
`INTERNAL_API_URL`'e giderken tenant Host'unu taşıması `hooks.server.ts` ile yapılır.

## 3. Manager (uygulama ici kontrol paneli — `Modules/Manager`)

Manager artik ayri bir proje degil; ayni uygulamanin icinde, ayri bir "control"
(main) veritabani kullanir. Panel yalniz `owner` rollu hesapla acilir.

```env
DB_CONTROL_HOST=127.0.0.1
DB_CONTROL_PORT=5432
DB_CONTROL_DATABASE=manager_shopera  # merkez/main DB (bir kez olusturulur)
DB_CONTROL_USERNAME=<kullanici>
DB_CONTROL_PASSWORD=<sifre>
MANAGER_HOSTS=manager.snaker.store   # panel host(lari), virgulle
MANAGER_BASE_DOMAIN=snaker.store
MANAGER_OWNER_EMAIL=owner@snaker.store
MANAGER_OWNER_PASSWORD=<guclu-sifre>
```

Kurulum (control DB):
```bash
php artisan manager:migrate --seed   # control semasi + feature/plan/tema + owner hesabi
php artisan manager:map              # tenant host->db haritasini control DB'den kur
php artisan manager:push             # effective entitlement/tema -> tenant DB'leri
```

- Panel: `https://manager.snaker.store` (yalniz `owner` rollu hesap).
- Zamanlayici (`schedule:run` cron): `manager:map` (5 dk) + `manager:report-usage` (saatlik).
- Yeni tenant: panelde host tanimla -> `tenant:provision` (panel otomatik cagirir) -> `manager:push`.
- Elle subdomain: nginx'te `server_name *.snaker.store;` + DNS wildcard kaydi yeterli; yeni subdomain icin nginx degisikligi gerekmez.
- TLS/DNS otomasyonu (Cloudflare) kaldirildi; subdomainler elle yonetilir.
- Deploy: `deploy/deploy.sh` (API + Svelte + admin + `manager:migrate|map|push`).
- `php artisan migrate` yalniz TENANT (default) DB'sini migrate eder; control DB icin `manager:migrate`.

## Notlar
- **TLS/DNS:** Subdomainler `*.base_domain` wildcard kaydıyla; custom domainler
  Manager `CloudflareDns` servisiyle otomatik (proxied) yönetilir.
- **Güvenlik:** Laravel `TRUSTED_PROXIES` üretimde Cloudflare/proxy IP'leri olmalı
  (X-Forwarded-Host'a güvenildiği için `*` bırakılmaz).
