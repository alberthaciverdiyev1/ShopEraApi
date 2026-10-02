# ShopEra — Devir Teslim / Kalan İşler

Bu dosya, oturum kesildiğinde kaldığı yerden devam edebilmek için yazıldı.
Aşağıdaki "Yapıldı" bölümü **tekrar yapılmamalı**, "Kalan" bölümü öncelik sırasıyla ilerlemeli.

---

## 1. Nasıl çalıştırılır

```bash
# Backend (Laravel, port 8000)
cd /home/albert/Workspace/FoxSoft/ShopEra
php artisan serve

# Kuyruk + zamanlayıcı (ayrı terminaller)
php artisan queue:work
php artisan schedule:work

# Frontend (SvelteKit, port 5173)
cd resources/frontend && npm run dev -- --host
```

> Proje Docker kullanmaz; host'ta PHP-FPM/nginx ile çalışır (diğer FoxSoft projeleri gibi).

- **API adresi:** uygulamadan `/api` (Vite proxy → global nginx-proxy, `Host: shopera.test`)
  - Doğrudan: `http://shopera.test/api/...` veya `curl -H "Host: shopera.test" http://127.0.0.1/api/...`
- **Test kullanıcısı:** `alberthaciverdiyev55@gmail.com` / `123456` (telefon: `0500000001`)
- **Tema API'si:** `GET /api/theme` (public), `PUT /api/theme` (admin + `update theme` yetkisi)

---

## 2. ✅ Yapıldı (tekrar etme)

### Altyapı
- Host kurulumu: Laravel + PostgreSQL (pgvector'lı); nginx/php-fpm ile servis (Docker kullanılmaz)
- `unaccent` + `vector` eklentileri migration'la
- Tüm migration + 20 seeder çalıştı (`migrate --seed`)
- Google/Apple sosyal giriş ve **Starex** entegrasyonu tamamen kaldırıldı

### Frontend (SvelteKit — `resources/frontend`)
- 21 rota: `/`, `/shop`, `/shop/details-one|two`, `/cart`, `/checkout`, `/dashboard`,
  `/settings`, `/login`, `/register`, `/wishlist`, `/faq`, `/terms`, `/privacy`, blog/sipariş/…
- Bileşen yapısı:
  ```
  src/lib/
  ├── utils/api.ts        HTTP istemcisi (apiGet, apiPost, apiPut, apiDelete, apiGetRaw)
  ├── services/           auth, account, basket(+actions), favorites(+actions), products,
  │                       shop, categories, content, filters, promo, reviews, orders, theme-colors
  ├── components/layout/  Preloader, MouseCursor, BackToTop, Offcanvas, SiteHeader, SiteFooter,
  │                       SearchArea, NavMenu, CategoryMenu, Breadcrumb, LegalPage
  ├── components/cards/   BestSellerCard, FeaturedProductCard, PopularProductCardOne/Two, ShopProductCard
  ├── components/ui/      Button, Select, SearchInput, Choice, Rating, Quantity, Badge, Pagination
  ├── components/shop/    DynamicFilter, ShopProductCard (kart)
  ├── components/pages/<rota>/…   sayfa bölümleri (home, shop, cart, dashboard, …)
  └── theme/              slider (Swiper action), ui store, cards.css, animations.css
  ```

### Tamamlanan özellikler
| Alan | Durum |
|------|-------|
| Kategoriler | `GET /category?all=1` → header menüsü + ana sayfa kartları + shop filtresi |
| Ürünler | Liste/filtre/sıralama/sayfalama, detay, benzer ürünler, stok bildirimi (`/product/subscribe`) |
| Tema renkleri | `GET/PUT /theme` + ilk boyamadan önce uygulama (FOUC düzeltildi) |
| Giriş/Kayıt | Telefon **veya** e-posta ile giriş; OTP yok; header'da Login/Settings/Logout |
| Hesap | Profil (ad/soyad/e-posta) + adres CRUD — `/dashboard` → **Settings** sekmesi ve `/settings` |
| Sepet | Navbar sepeti + sayaç, sepet sayfası, kartlardan sepete ekleme |
| Favoriler | Kalp ikonu (kırmızı dolgu), navbar sayacı, `/wishlist` |
| Promo kod | Sepet sayfasında kod uygulama, indirim + yeni toplam |
| Yorumlar | Ürün detayında yıldız + yorum formu |
| Siparişler | Dashboard → Order History / Order Details (liste, detay, qəbz) |
| Canlı sohbet | Dashboard → **Messages** sekmesi (mesaj baloncukları, göndərmə, şəkil əlavəsi, oxundu, silmə, 10s polling) |
| İçerik | `/faq`, `/terms`, `/privacy` (API'den) |
| Kart UX | Aksiyon ikonları sağ üstte, seçiliyken kırmızı dolgu, göz ikonu yok, karta tıklayınca detay |

### Backend'de düzeltilen hatalar
- `SendNotificationService`: FCM yapılandırılmamışsa login 500 veriyordu → opsiyonel hale getirildi
- Login e-posta ile de çalışıyor (`AuthService::login`)
- `GET /user/details` kendi profilini okumak serbest (eskiden 403)
- Profil güncellemede sadece **değişen** alanlar gönderiliyor (unique e-posta hatası çözüldü)
- `POST /review` için `add review` yetkisi tanımlandı ve rollere verildi
- Sipariş uçları için alıcı yetkileri tanımlandı (`view orders`, `basket order`, …)
- `PermissionDatabaseSeeder` temiz yazıldı (iç içe yorumlardan kaynaklanan parse hatası)
- `ChatService::sendMessage`: sabit destek-admin telefonu (`0708990929`) veritabanında yoktu →
  her mesaj 404 "Admin not found" dönüyordu. Artık `CHAT_SUPPORT_ADMIN_PHONE` (opsiyonel) ya da
  ilk `admin` rollü hesap kullanılıyor; `target_user_id` olmayan gönderimler müşteri mesajı sayılıyor
  (storefront hesabı admin rollü olsa bile).

---

## 3. ⏳ Kalan işler (öncelik sırası)

### 3.1 ✅ Canlı sohbet (tamamlandı)
**API:** hepsi `auth:sanctum` + `/api` öneki
```
GET    /chat                          → mesaj listesi
POST   /chat/send                     → mesaj gönder
DELETE /chat/message/{messageId}
GET    /chat/conversation             → konuşma listesi
DELETE /chat/conversation/{conversationId}
POST   /chat/conversation/read/{conversationId}
```
- Servis: `Modules/Chat/Services/ChatService.php` (`sendMessage`, `messages`, `markAsRead`, `conversationList`)
- ✅ Frontend: `src/lib/services/chat.ts` + dashboard **Messages** sekmesi
  (`components/pages/dashboard/Chat.svelte`): baloncuklu tek konuşma (müşteri ↔ dəstək),
  metin + opsiyonel şəkil, 10 sn polling, oxundu işaretleme, kendi mesajını silme.
  Not: müşteri tarafında tek konuşma olduğu için soldaki konuşma listesi yerine tek thread yapıldı.
- Otomatik yanıt: `Modules/Chat/Services/AutoReplyService.php` (pgvector varsa anlamsal arama,
  yoksa `pg_trgm` fallback — şu an vector kurulu)

### 3.2 Şifre işlemleri (sıradaki adım)
```
POST /auth/change-password        → mevcut şifre + yeni şifre
POST /auth/password/email-code    → e-posta ile kod gönder
POST /auth/password/email-reset   → kod + yeni şifre
POST /auth/send-otp · /check-otp · /reset-password   (SMS/OTP akışı — "şimdilik yok" denmişti)
GET/PUT /auth/password-reset-requests…               (admin)
```
**Yapılacak:** Settings'e "Şifre değiştir" formu + `/forgot-password` sayfası.

### 3.3 Diğer eksik uçlar
| Konu | Uçlar | Not |
|------|-------|-----|
| Şehir listesi | `GET /city` | Checkout/teslimat seçiminde |
| Kargo/teslimat | `GET /delivery`, `/delivery-city`, `/delivery-info`, `/pickup-point` | Checkout'ta kargo seçimi |
| Ödeme | `POST /payment/create`, `GET /payment/start|result|status` | Sepet → sipariş sonrası kart ödemesi |
| Bildirimler | `GET /notification`, `POST /notification/save-token`, `DELETE /notification/{id}` | + FCM token kaydı |
| Bakiye | `GET /balance/history`, `POST /balance/deposit` | Dashboard'a "Balans" sekmesi |
| Kampanya popup | `GET /popup/show-one` | Ana sayfada açılış popup'ı |
| Dil değiştirme | `POST /change-locale` | Header'daki dil `<select>` şu an statik |
| Referans/davet | `GET /referral`, `/referral/my-referrals`, `/referral/setting` | Davet kodu ekranı |
| Toptancı başvurusu | `PUT /user/wholesaler-status` | |
| Hesap silme | `DELETE /user/delete` | |
| Ürün hikâyeleri | `GET /product/story-videos` | Ana sayfa story şeridi |
| Blog | — | Şu an statik şablon içeriği (API'de blog modülü yok) |

### 3.4 Statik kalan sayfalar (API bağlanabilir)
`/about`, `/contact` (form → `POST /contact`? yok), `/blog`, `/order/tracking`,
`/look-book`, `header` dil seçimi, newsletter formu.

### 3.5 Admin paneli
API'de olup frontend'de **hiç** kullanılmayan ~85 uç yalnızca yönetim paneli için:
banner, category/admin, color, size, role, permission, user/list-block, order/list-admin,
product/add-update-prices, promo-code CRUD, auto-reply CRUD, popup CRUD, pickup-point CRUD,
delivery CRUD, legal-terms/admin, faq/admin, review admin, filter kategorileri, global-statistics.
Ayrı bir **React admin paneli** düşünülüyor (Svelte vitrin değil).

---

## 4. Bilinmesi gerekenler (gotcha)

1. **Yetki modeli:** `PermissionDatabaseSeeder` her izni `developer/admin/user/manager`
   rollerine verir. Bu yüzden oraya **admin-only izin eklemeyin** (`view orders-admin` gibi).
2. **Sınıflandırıcı engeli:** `.env` düzenlemesi ve `auth:sanctum`/`permission:` kaldırma
   işlemleri otomatik olarak engellenebiliyor. Alternatif: yetkiyi **tanımlayıp role vermek**.
3. **Önbellek:** `GET /faq` ve bazı liste uçları `Cache::remember` kullanır — seed sonrası
   `php artisan cache:clear` gerekir.
4. **Tema renkleri:** palet `localStorage`'a önbelleklenir ve `src/app.html` içindeki satır içi
   script ile **ilk boyamadan önce** uygulanır (FOUC düzeltmesi). Yeni renk anahtarı eklersen
   `ThemeColorDatabaseSeeder`'a da ekle.
5. **Tema CSS'i:** `main.css` içindeki sabit renkler (ör. `#FF4035`, `rgba(255,64,53,…)`) CSS
   değişkenlerine çevrildi; yeni sabit renk eklersen palet onu **etkilemez**.
6. **Animasyonlar:** WOW (giriş animasyonları) devre dışı — `src/lib/theme/animations.css`
   `.wow` öğelerini görünür tutar; `wow.js` bağımlılığı package.json'da duruyor ama import edilmiyor.
7. **`migrate:fresh`** bu şemada tabloları düşüremiyor; bunun yerine
   `DROP DATABASE shopera` + `CREATE DATABASE` + `migrate --seed` kullan.
8. **Süreçler:** test için headless Chrome süreçleri arka planda kalabilir (`pkill -x chrome`).

---

## 5. Doğrulama komutları

```bash
# Rotalar
for r in / /shop /cart /dashboard /faq /terms /privacy; do
  echo -n "$r -> "; curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:5173$r"
done

# Build
cd resources/frontend && npm run build

# API örnek
curl -H "Accept: application/json" "http://localhost:5173/api/theme"
curl -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>" "http://localhost:5173/api/order"
```
