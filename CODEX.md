# CODEX.md

Bu dosya, Codex'in Snaker deposunda calisirken uymasi gereken proje kurallarini ozetler. Daha ayrintili mimari bilgi icin `GEMINI.md`, frontend kurallari icin `resources/frontend/CODEX.md` ve `resources/frontend/GEMINI.md` dosyalarina bak.

## Proje Ozeti

Snaker, Laravel 12 tabanli moduler monolit bir e-ticaret ve marketplace API'si ile `resources/frontend/` altindaki SvelteKit 2 + Svelte 5 frontend uygulamasindan olusur.

- Backend: PHP 8.3+, Laravel 12, `nwidart/laravel-modules`, Sanctum, Spatie Permission, Spatie Translatable.
- Frontend: SvelteKit 2, Svelte 5 Runes, TypeScript, Vite, Bootstrap tema varliklari.
- API cevaplari `responseHelper()` standardini izler: `{ success, status_code, message, data, meta }`.

## Calisma Protokolu

1. Degisiklik yapmadan once ilgili controller, service, resource, type ve Svelte component dosyalarini oku.
2. Kapsami dar tut; istenmeyen refactor, paket guncellemesi veya toplu formatlama yapma.
3. Kullaniciya ait mevcut degisiklikleri geri alma. Kirli worktree varsa onunla uyumlu calis.
4. Sir dosyalarini okuma, loglama veya yanita tasima: `.env`, `storage/firebase/`, anahtar/cert dosyalari.
5. Yikici komutlar (`git reset --hard`, `git push --force`, `migrate:fresh`) kullanma.

## Backend Kurallari

- Katman: Controller ince kalir; is mantigi `Modules/<Module>/Services/` icinde, modeller `Modules/<Module>/Entities/` icindedir.
- Validation `Modules/<Module>/Http/Requests/` siniflarinda yapilir.
- Response formatini bozma; endpoint alan adlarini mobil ve web istemcileriyle geriye uyumlu tut.
- Cok dillilik icin `spatie/laravel-translatable` ve mevcut helper/resource kaliplarini koru.

## Frontend Kurallari

- `resources/frontend/` icin Svelte 5 Runes zorunludur.
- Legacy Svelte soz dizimi kullanma: `export let`, `$:`, `<slot />`, `on:click`.
- API cagirlari `$lib/api.ts` uzerinden yapilir: `apiGet`, `apiGetWithMeta`, `buildQuery`.
- Tarayiciya bagimli kutuphaneleri `onMount` icinde dinamik `import()` ile yukle.
- Frontend degisikliginden sonra `npm --prefix resources/frontend run check` calistir.

## Dogrulama

- Backend degisiklikleri: `./vendor/bin/pint` ve `php artisan test`.
- Frontend degisiklikleri: `npm --prefix resources/frontend run check`.
- Sadece analiz/dokuman degisikligi yapildiysa ilgili testlerin neden calistirilmadigini finalde belirt.
