# CODEX.md (Frontend)

Bu dosya `resources/frontend/` kapsaminda Codex icin hizli SvelteKit/Svelte 5 kurallarini tanimlar. Ayrintili aciklamalar icin ayni dizindeki `GEMINI.md` dosyasini oku.

## Temel Kurallar

1. Svelte 5 Runes zorunludur: `$state`, `$derived`, `$derived.by`, `$props`, `$effect`.
2. Svelte 4/legacy kaliplari kullanma: `export let`, `$:`, `<slot />`, `on:click`.
3. Event handler'lar HTML attribute seklindedir: `onclick`, `onsubmit`, `onchange`, `oninput`.
4. API istekleri dogrudan `fetch` ile degil `$lib/api.ts` uzerinden yapilir.
5. Laravel dizi sorgulari icin `buildQuery()` kalibini koru; parametreleri manuel string birlestirme ile kurma.
6. DOM ve browser-only kutuphaneleri SSR uyumlu tut; `window`/`document` ve Swiper/Fancybox benzeri importlari `onMount` icinde ele al.
7. Degisiklik sonrasi `npm run check` calistir.

## API Entegrasyon Hatirlatmasi

- Backend zarfi: `{ success, status_code, message, data, meta }`.
- Data icin `apiGet<T>()`, sayfalama/meta gereken yerlerde `apiGetWithMeta<T>()`.
- Cok dilli alanlarda mevcut helper'lari kullan: ornegin kategori adlari icin `categoryName`.
