# DEFERRED.md — Ertelenen işler / bilinen farklar

Bu dosya, taşıma sırasında **bilinçli olarak ertelenen** veya Laravel'den **farklı** yapılan
şeyleri listeler. Bir işi yaparken buraya bakıp "acaba unuttuk mu?" karışıklığını önler.
İş tamamlandıkça ilgili satırı **sil** (veya ✅ ile işaretle).

---

## 0) Ortak / kontrat farkları

- **Translatable çıktı:** `title`, `description`, `name`, `html`, `seller_instructions` gibi çevrilebilir
  alanlar bizde **map** (tüm diller) olarak dönüyor. Laravel aktif locale'de **string** döndürüyordu.
  (Etkilenen: Category.name, Product.title/description, Faq.title/description, LegalTerm.html.)
  → Düzeltme: global bir `locale` resolver + `Trans()` ile çıktıyı string'e çevirmek.
- **Google Translate yok:** eksik diller `az`'dan kopyalanıp case dönüşümü uygulanıyor (gerçek çeviri değil).
  (category/faq/legalterm/product — `TODO: Google Translate`).
- **Cache yok:** Laravel'deki `Cache::remember`/`forget` (settings, brands_list, faq_list, product view,
  add_product lock) uygulanmadı.
- **Auth:** JWT kullanılıyor. Laravel **Sanctum** token (`personal_access_tokens`) uyumluluk katmanı **yok**.
- **Middleware eksikleri:** CORS, rate-limit (OTP için önemliydi), request logging başlıkları — `gin.Default`
  (logger+recovery) var, gerisi yok.
- **Swagger/OpenAPI** (`/docs`) yok.
- **`users.is_wholesaler`** alanı duruyor (wholesale fiyatları kaldırıldı ama bu kullanıcı bayrağı bırakıldı).

---

## 1) Altyapı / Storage

- **File upload → local `storage/app/public`.** Bunny CDN sonra eklenecek.
  → Yapılacak: `SaveUpload`/`StorageURL` arkasına **Storage arayüzü** + `Local` ve `Bunny` implementasyonları.
- **Görsel/video sıkıştırma** (`compressAndUploadImage/Video`) yok — dosyalar ham kaydediliyor.
- **Görsel hash (ahash/dhash) + embedding** yok; `GenerateMissingProductImageHashes/Embeddings`
  komutları taşınmadı.
- **Image URL:** `helpers.StorageURL` `APP_URL` + `/storage/...` üretir; CDN geçişinde burası değişecek.

---

## 2) DB / Migration notları

- Şema hâlâ PostgreSQL'de (Laravel yönetiyordu). **Yeni eklenen kolon/tablolar için SQL migration'lar var:**
  - `migrations/2026_09_25_000000_add_story_videos_enabled_to_settings.sql`
  - `migrations/2026_09_25_000100_create_filter_tables.sql`
  → **Bu SQL'ler canlı DB'ye uygulanmalı.**
- `product_price_history` tablosuna **yazılmıyor** (Laravel'in bu sürümünde de yazmıyordu).
- `filters/category_filters/product_filters` tabloları **yeni** (TicarXCaspian'dan uyarlandı; Old'da yok).

---

## 3) Modül bazlı ertelenenler

### Product
- [ ] Görsel **hash/embedding** üretimi.
- [ ] `product_price_history` kaydı + `updatePrices` geçmişi.
- [ ] Stok **0→>0** geçişinde `notifySubscribers` (Notification modülü gerekir).
- [ ] **Kişiselleştirilmiş** `recommend` (kullanıcının siparişlerinden en çok alınan kategori — **Order** gerekir).
- [ ] **Admin list** davranışı (`is_admin` + `is_active`; şu an hep public filtre).
- [ ] Görsel `color_id` **"keepColor"** temizliği (ürün update'inde stale color null'lama).
- [ ] Admin update **override**'ları (`add/update(..., $overrides)`).
- [ ] Sıkıştırma + Bunny CDN.

### Address / Delivery
- [x] Address (user/address CRUD) + City & Towns (`GET /cities`) taşındı.
- [ ] Kalan Delivery: DeliveryInfo, PickupPoint, teslimat ücretleri, city CRUD/admin.

### Category
- [ ] `GET /category/with-products` (Product + favoriler gerekir).
- [ ] Görsel yükleme (add/update `image` dosyası).
- [ ] `sort_order` reorder: Laravel'in tam davranışı kısmen uygulandı.

### Brand
- [ ] Görsel yükleme.
- [ ] `Cache::forget('brands_list_*')`.

### Setting
- [ ] `GET /global-statistics` (Order + Delivery/City gerekir).
- [ ] Settings cache.

### HelpAndPolicy
- [ ] `privacy-and-policy` ham HTML endpoint'i (Laravel `LegalTermsService::privacyAndPolicy`).
- [ ] Legal term **HTML içi çeviri** (DOM tabanlı) — şimdilik sadece az fallback.
- [ ] `list` / `list-admin` çıktısı **map** (bkz. §0 translatable).

### User / Auth (yarım)
- [ ] **OTP**: `send-otp`, `check-otp`.
- [ ] **Şifre sıfırlama**: `reset-password`, `password/email-code`, `password/email-reset`,
      `change-password`, `password-reset-requests`.
- [ ] **Profil**: `change-name/surname/email/phone`, `admin-change-password`.
- [ ] **Sosyal giriş**: Google/Apple (orta vadede).
- [ ] Referral modülü (`add()` şu an no-op'tu; ilgili repository kaldırıldı).

### Review
- [x] list / list-admin / add / change-status / delete / admin-delete taşındı.
- [ ] Ürün görüntülenme sayacı (`views` increment + cache) — Laravel review list'te artırıyordu.

### Filter (yeni — TicarXCaspian)
- [x] `GET /filters`, `GET /category-filters?category_id=`, ürün `?filters[<id>]=<value>`.
- [ ] **Admin CRUD**: filter oluştur/güncelle/sil, kategoriye atama, ürüne değer atama.

---

## 4) Henüz taşınmayan modüller (Old/ içinde)

- [ ] **Order** (+ OrderItem, OrderStatus) — Product recommend/statistics ve subscribe notify'ı da buna bağlı.
- [ ] **Payment** (Epoint), **Balance**, **PromoCode**.
- [ ] **Delivery** — City + Towns ✅ taşındı; **DeliveryInfo, PickupPoint, teslimat ücretleri** bekliyor.
- [ ] **Notification** (FCM push, token, admin toplu bildirim) — subscribe notify için gerekli.
- [ ] **Chat**, **Live**.
- [ ] **Listing** (ilan/classifieds).
- [ ] **RoleAndPermissions** (spatie roller — auth'ta `assignRole` de kaldırıldı, register artık rol atamıyor).
- [ ] **Store** — **taşınmayacak** (bilinçli kaldırıldı; ilgili kolonlar/ayarlar silindi).
- [ ] User'ın kalan parçaları (Address, Referral).  (Basket ✅ ve Favorite ✅ taşındı.)

---

## 5) Kod içi TODO'lar (hızlı referans)

- `internal/modules/product/helpers/product_translations.go` → `TODO: Google Translate`
- `internal/modules/category/helpers/category_name.go` → `TODO: Google Translate`
- `internal/modules/helpandpolicy/helpers/translations.go` → `TODO: Google Translate`
- `internal/modules/setting/...` story toggle migration uygulanmalı.
