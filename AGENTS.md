# AGENTS.md

Bu dosya, Antigravity, subagent'lar (araştırma, kod yazma, test) ve diğer otonom AI ajanlarının ShopEra deposunda çalışırken uyması gereken temel protokolleri tanımlar. Detaylı teknik yönergeler ve mimari standartlar için [`GEMINI.md`](file:///home/albert/Workspace/FoxSoft/ShopEra/GEMINI.md) ve [`resources/frontend/GEMINI.md`](file:///home/albert/Workspace/FoxSoft/ShopEra/resources/frontend/GEMINI.md) dosyalarına başvurun.

---

## 1. Görev Dağılımı ve Odak Alanları

### Frontend Odaklı Görevler (`resources/frontend/`)
- **Öncelikli Teknoloji**: SvelteKit 2 + **Svelte 5 (Runes zorunlu)** + TypeScript.
- **Kesin Kural**: Svelte 4 / legacy kalıplar (`export let`, `$:`, `<slot />`, `on:click`) KULLANILMAZ.
- **Kılavuz**: Tüm Svelte geliştirme kuralları için [`resources/frontend/GEMINI.md`](file:///home/albert/Workspace/FoxSoft/ShopEra/resources/frontend/GEMINI.md) incelenmelidir.
- **Doğrulama**: Değişiklik sonrası `npm --prefix resources/frontend run check` çalıştırılmalıdır.

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
