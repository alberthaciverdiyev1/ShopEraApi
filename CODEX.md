# CODEX.md

Bu dosya, Codex'in Snaker deposunda calisirken uymasi gereken proje kurallarini ozetler. Daha ayrintili mimari bilgi icin `GEMINI.md` dosyasina bak.

## Proje Ozeti

Snaker, Laravel 12 tabanli moduler monolit bir e-ticaret ve marketplace uygulamasidir. Mobil uygulamalar REST API'yi tuketir; web magazasi ve admin paneli Laravel icinde Blade olarak sunulur.

- Backend: PHP 8.3+, Laravel 12, `nwidart/laravel-modules`, Sanctum, Spatie Permission, Spatie Translatable.
- Web: Blade + Vite 7 + saf JavaScript, Bootstrap tabanli magaza temasi.
- API cevaplari `responseHelper()` standardini izler: `{ success, status_code, message, data, meta }`.

## Calisma Protokolu

1. Degisiklik yapmadan once ilgili controller, service, resource ve Blade view dosyalarini oku.
2. Kapsami dar tut; istenmeyen refactor, paket guncellemesi veya toplu formatlama yapma.
3. Kullaniciya ait mevcut degisiklikleri geri alma. Kirli worktree varsa onunla uyumlu calis.
4. Sir dosyalarini okuma, loglama veya yanita tasima: `.env`, `storage/firebase/`, anahtar/cert dosyalari.
5. Yikici komutlar (`git reset --hard`, `git push --force`, `migrate:fresh`) kullanma.

## Backend Kurallari

- Katman: Controller ince kalir; is mantigi `Modules/<Module>/Services/` icinde, modeller `Modules/<Module>/Entities/` icindedir.
- Validation `Modules/<Module>/Http/Requests/` siniflarinda yapilir.
- Response formatini bozma; endpoint alan adlarini mobil ve web istemcileriyle geriye uyumlu tut.
- Cok dillilik icin `spatie/laravel-translatable` ve mevcut helper/resource kaliplarini koru.

## Web Kurallari

- Magaza sayfalari `resources/views/storefront/` ve `resources/views/components/storefront/` altinda Blade ile yazilir.
- Uygulama seviyesinde yalnizca `app/Http/Controllers/Admin/` ve `app/Http/Controllers/Web/` vardir; API controller'lari modul icindedir.
- `Web/` controller'lari DB sorgusu yazmaz ve `app/Services/` kullanmaz. Modul controller'ini `Request::create(...)` + `setUserResolver(...)` ile cagirir ve JSON zarfini view verisine cevirir.
- Etkilesimler `resources/js/storefront.js` icinde saf JavaScript ile yazilir.
- Admin assetleri `resources/css/admin.css` ve `resources/js/admin.ts`, magaza
  assetleri `resources/css/storefront.css` ve `resources/js/storefront.js` altindadir.
- Web degisikliginden sonra `npm run build` calistir.

## Dogrulama

- Backend degisiklikleri: `./vendor/bin/pint` ve `php artisan test`.
- Web degisiklikleri: `npm run build`.
- Sadece analiz/dokuman degisikligi yapildiysa ilgili testlerin neden calistirilmadigini finalde belirt.
