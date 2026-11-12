# AGENTS.md

Bu dosya, Antigravity, subagent'lar (araştırma, kod yazma, test) ve diğer otonom AI ajanlarının Snaker deposunda çalışırken uyması gereken temel protokolleri tanımlar. Detaylı teknik yönergeler ve mimari standartlar için [`GEMINI.md`](file:///home/albert/Workspace/FoxSoft/Snaker/GEMINI.md) dosyasına başvurun.

---

## 1. Görev Dağılımı ve Odak Alanları

### Web Odaklı Görevler (`resources/views/`, `resources/js/`, `resources/css/`)
- **Teknoloji**: Laravel Blade + Vite 7 + saf JavaScript.
- **Kural**: Mağaza ve admin sayfaları Blade ile sunulur; Svelte/SvelteKit katmanı kaldırılmıştır. Yeni istemci kodu Blade + `storefront.js` içinde yazılır.
- **Doğrulama**: Değişiklik sonrası `npm run build` çalıştırılmalıdır.

### Backend Odaklı Görevler (`app/`, `Modules/`)
- **Mimari**: Laravel 12 Modüler Monolit (`nwidart/laravel-modules`).
- **Katman**: Controller (ince) → Service (iş mantığı) → Entity (Eloquent modelleri `Modules/<Modul>/Entities/`).
- **API Standardı**: Tüm cevaplar `responseHelper()` ile döner. API sözleşmesini bozmayın.
- **Doğrulama**: Değişiklik sonrası `./vendor/bin/pint` ve `php artisan test` çalıştırılmalıdır.

---

## 2. Ajan Çalışma Protokolü

1. **Ajan İnceleme Aşaması (Research First)**:
   - Kod yazmadan veya düzenlemeden önce ilgili bileşeni, servisi ve tip tanımlarını tam olarak oku.
2. **Kapsamı Sınırlı Tutma**:
   - Talep edilmeyen refactor, paket güncellemesi veya formatlama yapma. Yalnızca istenen değişikliği uygula.
3. **Güvenlik ve Gizlilik**:
   - `.env`, veritabanı şifreleri, Firebase kimlik dosyaları (`storage/firebase/`), Starex ve Stripe anahtarlarına asla erişme, bunları loglama veya yanıtlara ekleme.
4. **Bağlam Yönetimi**:
   - `.geminiignore` ve `.agentignore` dosyalarında belirtilen dizinleri (`vendor/`, `node_modules/`, `build/`, `storage/logs/`, büyük medya ve zip dosyaları) bağlama dahil etme.
5. **Yıkıcı Komut Yasağı**:
   - `git reset --hard`, `git push --force`, `migrate:fresh` (özellikle üretimde) gibi geri dönülemez komutları çalıştırma.
