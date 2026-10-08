# CLAUDE.md

Bu dosya, Claude Code'un bu depoda çalışırken uyması gereken kuralları ve projeye
dair bağlamı tanımlar. Talimatlar Türkçe yazılmıştır; kod, değişken ve commit
içerikleri mevcut projedeki İngilizce konvansiyonunu korur.

 her degisikligi benim adimdan ingilizce commit at ve ssh ile push et

## Proje Özeti

**Snaker.az** — Laravel 12 tabanlı, çok dilli (az/en/ru/tr) bir e-ticaret ve
pazaryeri (marketplace) backend'i. Uygulama API-first çalışır; mobil uygulamalar ve
web arayüzü aynı REST API'yi tüketir. Kod tabanı **nwidart/laravel-modules** ile
modüler monolith olarak organize edilmiştir.

- PHP `^8.3`, Laravel `^12`
- Veritabanı: PostgreSQL (testler SQLite in-memory)
- Önbellek/session/queue: Redis/file + database
- Frontend build: Vite 7 + Tailwind 4

## Sık Kullanılan Komutlar

```bash
# Geliştirme (server + queue + log + vite birlikte)
composer dev

# Sadece sunucu
php artisan serve

# Testler (önce config:clear çalışır)
composer test
php artisan test
php artisan test --filter=SomeTest

# Kod formatlama (Laravel Pint)
./vendor/bin/pint
./vendor/bin/pint Modules/Product

# Veritabanı
php artisan migrate
php artisan migrate:fresh --seed

# Kuyruk (geliştirme)
php artisan queue:listen --tries=1

# Log takibi
php artisan pail --timeout=0

# Frontend
npm run dev
npm run build
```

## Dizin Yapısı ve Mimari

### Kök `app/` — paylaşılan çekirdek
- `app/Helpers/` — global helper fonksiyonları (`autoload.files` ile yüklenir).
  Yeni helper eklerken dosyayı `composer.json` içindeki `autoload.files` listesine de ekle.
- `app/Abstracts/Crud.php`, `app/Interfaces/` — CRUD sözleşmeleri.
- `app/Enums/` — `OrderStatus`, `BalanceType`, `AddressType`, `Gender`, `ReviewStatus`, `City`.
- `app/Jobs/` — kuyruk işleri (Starex gönderi, FCM push, bildirim).
- `app/Services/` — çekirdek servisler (Starex entegrasyonu, bildirim, sipariş).
- `app/Models/User.php` — yalnızca User kök modeli; diğer modeller modüllerin içinde.

### `Modules/<Ad>/` — her iş alanı bir modül
Modül içi standart yapı:
```
Modules/Product/
├── Config/config.php
├── Console/                 # Artisan komutları
├── Database/
│   ├── Migrations/          # Modüle ait migration'lar
│   ├── Factories/
│   └── Seeders/
├── Entities/                # Eloquent modelleri (Model DEĞİL, Entities!)
├── Http/
│   ├── Controllers/         # İnce: sadece servise delege eder
│   ├── Requests/            # FormRequest doğrulama
│   └── Resources/           # API Resource dönüşümleri
├── Providers/               # ServiceProvider + RouteServiceProvider
├── Routes/api.php           # Ana route dosyası (diğerlerini require eder)
├── Routes/<x>Route.php
├── Services/                # İş mantığının tamamı burada
└── Tests/
```

Aktif modüller `modules_statuses.json` ile yönetilir. Yeni modül aç/kapat:
`php artisan module:enable <Ad>` / `module:disable <Ad>`.

## Kod Konvansiyonları

### Katmanlama (çok önemli)
- **Controller → Service → Entity** akışı zorunludur. Controller içinde iş mantığı
  veya doğrudan Eloquent sorgusu yazma; mantığı `Services/` içine koy.
- Modeller modül kökündeki `Entities/` altında yaşar (asla `Http/` içinde değil) ve
  `Modules\<Ad>\Entities` namespace'ini kullanır (Laravel'in varsayılan `App\Models`'ı değil).
- Doğrulama daima `Http/Requests/*Request.php` içinde (`prepareForValidation()` ile
  input normalizasyonu yapılır); controller'da inline `validate()` kullanma.
- API cevapları `Http/Resources/` ile şekillendirilir.

### Cevap formatı
Tüm API cevapları `responseHelper()` global helper'ı ile döner:
```php
return responseHelper('messages.key', 200, $resource);
```
Mobil istemciler `?is_application=1` gönderdiğinde düz veri (sarmalayıcı olmadan),
aksi halde `{success, status_code, message, data}` zarfı döner. Bu davranışı bozma.

### Route'lar
- Modül route'ları `Routes/api.php` içinde, başka dosyaları `require` ederek gruplanır.
- Route'lar prefix + isimlendirme kuralına uyar (`product.list`, `product.details`).
- Sayısal parametrelerde `->whereNumber('id')` kullanılır.
- Yetkilendirme iki katmanlıdır: route'ta `auth:sanctum`, controller constructor'ında
  `$this->middleware('permission:...')` (spatie/laravel-permission).

### Çok dillilik
- Çevrilebilir alanlar `spatie/laravel-translatable` ile `az/en/ru/tr` dillerinde tutulur.
- Modelde `public array $translatable = [...]` tanımlanır; alanlar JSON/array cast edilir.
- Otomatik çeviri için `App\Helpers\TranslateHelper::translate()` kullanılır (kaynak dil `az`).
- Kullanıcı diline göre `SetLocaleFromHeader` middleware'i ile locale ayarlanır.

### Genel stil
- PSR-12 + Laravel Pint. Girinti 4 boşluk, LF satır sonu, UTF-8 (bkz. `.editorconfig`).
- İsimlendirme: sınıflar `PascalCase`, metot/değişken `camelCase`, DB kolonları `snake_case`.
- İlişkilerde return tip bildirimi (`BelongsTo`, `HasMany`) kullanılır.
- Soft delete gerektiğinde `SoftDeletes` trait'i kullanılır.

## Veritabanı
- Migration'lar ilgili modülün `Database/Migrations/` klasörüne yazılır; tablo adları
  çoğul ve `snake_case` (`products`, `product_reviews`).
- Üretimde PostgreSQL, testlerde SQLite kullanılır. Bu nedenle PostgreSQL'e özgü
  fonksiyonları (örn. `ILIKE`, JSONB operatörleri) SQLite'ta kırılmayacak şekilde yaz.
- Sorgularda N+1'den kaçın: `with()`, `withCount()`, `withAvg()` kullan.
- Sık kullanılan liste sorguları için cache anahtar süreleri `.env` değişkenleriyle
  yönetilir (`BRAND_LIST_CACHE_TIME`, `PRODUCT_LIST_CACHE_TIME` vb.).

## Test
- PHPUnit kullanılır (`tests/Unit`, `tests/Feature`). Modül testleri `Modules/<Ad>/Tests/`.
- Test ortamı `.env.testing` benzeri phpunit.xml değişkenleriyle çalışır (SQLite :memory:).
- Yeni iş mantığı için Feature/Unit test ekle. Mevcut testleri kırma.
- Test çalıştırmadan önce `php artisan config:clear` (composer test zaten yapar).

## Claude İçin Kurallar

1. **Önce keşfet, sonra yaz.** Değiştireceğin modülün `Services/`, `Entities/` ve
   `Routes/` dosyalarını oku; mevcut deseni taklit et.
2. **Kapsamı dar tut.** İstenmeyen refactor, yeniden adlandırma veya stil değişikliği
   yapma. İlgisiz dosyalara dokunma.
3. **Sırları asla commit etme / loglama.** `.env`, `storage/firebase/*.json`, API
   anahtarları (Starex, FCM, Sentry) kritiktir — değerlerini çıktıya yazma.
4. **API sözleşmesini koru.** Cevap zarfı, route isimleri ve alan adları mobil
   uygulamalar tarafından ayrıştırılır; geriye dönük uyumsuz değişiklik yapma.
5. **Yeni bağımlılık eklemeden önce sor.** `composer require` / `npm install`
   gerektiren çözümler için önce mevcut paketlerle çözüm ara.
6. **Migration'ları düzenleme, yenisi ekle.** Yayınlanmış migration dosyalarını
   değiştirmek yerine yeni migration oluştur.
7. **Commit mesajları** proje diline uygun, kısa ve açıklayıcı İngilizce olsun.
8. Değişiklik sonrası ilgili testleri ve `./vendor/bin/pint` çalıştır.

## Çok Kiracılılık (Multi-Tenancy)

Tek kod tabanı, her tenant için **ayrı PostgreSQL veritabanı**. Free ve pro abone
aynı projeyi kullanır; ayrım instance değil, entitlement'larla yapılır.

- **Çözümleme:** `ResolveTenant` middleware `Host`'a göre `tenant_map`
  (`host => database`) cache'inden DB seçer ve default connection'ı `tenant` yapar.
  `TENANCY_ENABLED` (varsayılan `true`) kapalıysa devre dışı kalır. Map boşsa
  (henüz `manager:sync` çalışmamış) production dışında merkezi DB'ye düşer;
  production'da eşleşmeyen host her zaman 404 (yanlış tenant'a sızma yok).
- **Harita kaynağı:** Manager.Snaker. `php artisan manager:sync` `/api/v1/tenants`
  yanıtını `tenant_map` + `tenant_metadata` olarak cache'ler (3600 sn). Bir tenant
  birden çok host (custom domain + subdomain) bildirebilir; hepsi aynı DB'ye gider.
- **DB adı:** `App\Support\TenantDatabase::nameFor($host)` — tek doğruluk kaynağı
  (`ResolveTenant`, `tenant:provision`, `manager:sync` bunu kullanır).
- **Sağlama:** `php artisan tenant:provision {host}` (webhook `tenant.provision`
  ile otomatik de tetiklenir). Durum: `php artisan tenant:list`.
- **Entitlement/abonelik:** Manager'dan senkronlanan anlık görüntü tenant DB'sindeki
  `tenant_subscriptions` / `tenant_entitlements` / `tenant_promo_blocks` tablolarında
  tutulur; `Features` / `Subscription` sınıfları okur (senkron yoksa fail-open).
- **Route kapıları:** `feature:<key>` middleware (404), admin panelde `subscribed`
  (yazma engeli). Bkz. `Modules/*/Routes/api.php` ve `routes/admin.php`.
- **Storage:** `TenantContext::storagePath()` dosyaları `storage/app/public/<slug>/`
  altına yazar. Slug `storage_root` (Manager) → tenant DB adı → host sırasıyla
  türetilir; böylece custom domain + subdomain aynı klasörü paylaşır.
- **Kuyruk:** işler dispatch anında tenant bağlamını taşır
  (`App\Jobs\Concerns\TenantAware` + `RestoreTenantContext` middleware). `database`
  kuyruğu merkezi bağlantıya sabittir (`config/queue.php`); tek bir worker
  (`php artisan queue:work`) tüm tenant'lar için yeterlidir. Yeni bir `ShouldQueue`
  işine `TenantAware` ekle ve constructor sonunda `$this->captureTenant()` çağır;
  **model yerine id serialize et** (model tenant bağlantısı kurulmadan hydrate olur).
- **Zamanlanmış görevler:** tenant-farkında olmalı. `TenantDatabase::each()` ile her
  DB'de bir kez çalışır (bkz. `routes/console.php`, `db:backup`).
- **İlgili env:** `TENANCY_ENABLED`, `TENANCY_CACHE_STORE` (connection'dan bağımsız
  store; `redis`/`file`), `DB_QUEUE_CONNECTION`, `MANAGER_URL`, `MANAGER_TOKEN`,
  `MANAGER_API_KEY`, `MANAGER_WEBHOOK_SECRET`, `MANAGER_SITE_HOST`,
  `TRUSTED_PROXIES` (üretimde proxy IP'leri; `*` bırakma).
- **Domain/Cloudflare:** TLS Cloudflare edge'de biter; origin düz HTTP olabilir.
  Host nginx'i **catch-all** (`server_name _;`) olmalı: tek blokla her `Host`'u karşılar,
  tenant `Host`'tan çözülür — domain başına vhost gerekmez. Subdomain DNS'i Manager'da
  wildcard ile, custom domain DNS'i Manager `CloudflareDns` servisi ile otomatik yönetilir.
- **SSR:** `resources/frontend/src/hooks.server.ts` SSR API çağrılarına
  `X-Forwarded-Host`/`X-Forwarded-Proto` ekler; bu yüzden origin'de doğru proxy güveni
  (`TRUSTED_PROXIES`) şarttır, aksi halde Host taklit edilebilir.

## Bilinmesi Gereken Entegrasyonlar
- **Starex** — lojistik/gönderi entegrasyonu (`app/Services/Starex/`, webhook:
  `POST /api/integrations/starex/webhook`). `.env`'de `STAREX_*` değişkenleri.
- **Firebase/FCM** — push bildirimleri (`laravel-notification-channels/fcm`).
- **Laravel Reverb / Pusher** — realtime broadcast (chat, live).
- **Sentry** — hata izleme.
- **BunnyCDN** — görsel/medya depolama (flysystem).

## Yasaklar
- `.env` dosyasına veya gerçek sırlara erişme/okuma.
- `vendor/`, `node_modules/`, `public/build/`, `storage/logs/` içeriğini elle düzenleme.
- Üretim veritabanına bağlanma veya `migrate:fresh`'i üretimde çalıştırma.
- `git push --force`, `git reset --hard` gibi yıkıcı git komutlarını sormadan çalıştırma.
