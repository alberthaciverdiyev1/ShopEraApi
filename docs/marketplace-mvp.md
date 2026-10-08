# Marketplace (Vahid Elan Platforması) — MVP Planı

> Status: plan / təsdiq gözləyir. Branch: `feature/marketplace`.
> tap.az / turbo.az / lalafo.az tipli **vahid** platforma: bir sayt, çox satıcı.

## 1. Vizyon və prinsiplər

- **Vahid platforma** (tenant-başına deyil) — bütün elanlar/satıcılar bir sistemdə.
- **Platforma sadəcə platformadır**: satışa görə məsuliyyət satıcının üzərindədir.
- **Komissiya YOXDUR** — gəlir yalnız premium/irəli çəkilmiş elanlardan (gələcəkdə mağaza açılış haqqı).
- **Satıcı kateqoriyaları**:
  1. **Vendor** — mağazası olan satıcı (normal sifariş axını, sifariş vendora bağlı).
  2. **Sadə user** — mağazası olmayan qeydiyyatlı istifadəçi (əlaqə əsaslı elan).
  3. **Qonaq** — heç qeydiyyatdan keçməmiş şəxs elan yerləşdirə bilər.
- **Moderasiya** MVP-də yoxdur; data yığıldıqca admin təsdiqi + AI review sonra əlavə olunacaq.

## 2. Əsas model (entitilər)

- **Vendor / Mağaza** (`vendors`): `user_id`, `name`, `slug`, `logo`, `description`, `phone`, `address`, `status`, sosial linklər.
- **Elan** (mövcud `products` genişləndirilir):
  - `seller_type` = `guest|user|vendor`
  - `vendor_id` (nullable), `user_id` (nullable — qonaqda boş)
  - qonaq üçün: `contact_name`, `contact_phone`, `contact_email`
  - ərazi/şəhər, vəziyyət (yeni/işlənmiş), `price`, `category_id`
  - `is_promoted`, `promoted_until`, `is_premium` / `premium_until`
  - status sahələri (MVP-də default aktiv; sonra moderasiya)
- **Rollar**: yeni `vendor` rolu (mövcud `admin|user|...` üzərinə).

## 3. MVP fazaları (cavablara uyğun)

### Faza 0 — Baza model & rollar
- [ ] `vendors` cədvəli + model.
- [ ] Elan modelinə `seller_type`, `vendor_id`, qonaq əlaqə sahələri, `is_promoted/promoted_until`, şəhər/vəziyyət.
- [ ] `vendor` rolu (spatie, guard `sanctum`); `user_id` nullable (qonaq elanlar).
- [ ] Multi-tenant sualı həll olunur (bax §5) — vahid DB qərarı.

### Faza 1 — Elan yerləşdirmə (əsas axın)
- [ ] Public elan formu: qonaq (ad + telefon + təsvir + şəkillər + kateqoriya + qiymət + şəhər) yerləşdirə bilər (captcha).
- [ ] Qeydiyyatlı user elanı hesabına bağlanır.
- [ ] Vendor elanı mağazasına bağlanır.
- [ ] Şəkil yükləmə; "mənim elanlarım" (user/vendor üçün).

### Faza 2 — Mağaza (vendor)
- [ ] Özü qeydiyyatdan keçmə (vendor registration) → mağaza profili.
- [ ] Public mağaza səhifəsi (loqo, təsvir, elanlar).
- [ ] Vendor paneli `/vendor`: öz elanlarını və profilini idarə et.

### Faza 3 — Vitrin & axtarış
- [ ] Elan detay səhifəsi: qalereya, qiymət, satıcı/mağaza, əlaqə (telefon/WhatsApp/Chat).
- [ ] Axtarış + filter (kateqoriya, şəhər, qiymət aralığı), sıralama (irəli çəkilmişlər yuxarı).
- [ ] Ana səhifə: seçilmiş/irəli çəkilmiş elanlar.

### Faza 4 — Monetizasiya (komissiyasız)
- [ ] **İrəli çəkmə (promote/boost)** paketləri — mövcud `Payment` modulu ilə ödəniş.
- [ ] **Premium (VIP) elan** paketləri.
- [ ] Reklam bannerləri (mövcud `Banner` modulu) — əlavə gəlir.
- [ ] (Gələcək) mağaza açılışı üçün ödəniş.

## 4. MVP-dən SONRA

### Faza 5 — Moderasiya & təsdiq
- [ ] Admin təsdiqi (elan/mağaza) + AI ilə avtomatik review.

### Faza 6 — Sifariş axını (yalnız mağaza elanları)
- [ ] Səbət/checkout **yalnız vendor (mağaza)** elanları üçün.
- [ ] Sifariş vendora bağlanır; status/tracking; vendor–alıcı mesajlaşması (Chat).

## 5. QƏRAR: Tək database (multi-tenant ləğv edilir)

> **Qərar:** Sistem **tək (vahid) database** üzərində işləyəcək. Multi-tenancy
> (hər host → ayrı DB) ləğv edilir. Bütün satıcılar/mağazalar/elanlar bir DB-də.

### Mövcud mexanizm (nəyi dəyişmək lazımdır)
- `config/tenant.php` → `enabled` açarı (`TENANCY_ENABLED`).
- `App\Http\Middleware\ResolveTenant` — host-a görə `tenant` connection seçir.
- İki connection: `tenant` (runtime) və `control` (Manager üçün mərkəzi DB).
- `App\Support\TenantContext` / `TenantDatabase` — storage prefiksi, cache açarları.
- `manager:map`, `tenant:provision`, `tenant:list`, `tenant:migrate` əmrləri.
- Storage: hər tenant üçün ayrı qovluq (`storageSlug`).

### Faza 0 — Tək DB keçidi (addımlar)
- [ ] `TENANCY_ENABLED=false`; `ResolveTenant`-i tək DB üçün söndür/sadələşdir.
- [ ] `control` connection-ı tək DB-yə yönləndir (Manager cədvəlləri eyni DB-də).
- [ ] Storage prefiksini sil (`TenantContext::storagePath` → olduğu kimi qaytar).
- [ ] **Data miqrasiyası**: mövcud tenant DB-ləri + control → tək DB-yə birləşdir.
- [ ] `manager:map` / `tenant:provision` / `tenant:*` əmrlərini çıxar və ya no-op et.
- [ ] Subscription/entitlement (control) cədvəllərini tək DB-yə köçür.

> **Diqqət:** Data miqrasiyası geri dönməzdir. İcra etməzdən əvvəl mövcud DB-lərin
> siyahısı və birləşdirmə qaydası (host → satıcı/mağaza) təsdiqlənməlidir.

## 6. Açıq suallar
- Qonaq elanlar üçün moderasiya tam yoxdursa, spam/captcha necə idarə olunur?
- Elan limiti (pulsuz) nə qədər olmalıdır?
- Şəhər/ərazi siyahısı mövcud `Delivery` modulundan götürülsün?
- İrəli çəkmə qiymətləri (paketlər) hansılardır?
