# Svelte 5 Frontend Geliştirici Kılavuzu & Kuralları (resources/frontend)

Bu dosya, `resources/frontend/` dizini altındaki SvelteKit 2 + Svelte 5 uygulamasında çalışacak olan Antigravity ve Gemini ajanları için özel olarak hazırlanmıştır. Bu dizinde yapılan tüm geliştirmelerde buradaki prensipler ve standartlar temel alınmalıdır.

---

## 1. Mimari Genel Bakış

ShopEra frontend katmanı, modern bir headless e-ticaret ön yüzüdür:
- **Framework**: SvelteKit 2 (`@sveltejs/kit` ^2.63)
- **Reactivity Motoru**: **Svelte 5** (`svelte` ^5.56) — **Runes Modu Kesinlikle Zorunludur**
- **Tip Sistemi**: TypeScript (`typescript` ^6.0, `svelte-check` ^4.6)
- **Derleme Aracı**: Vite 8 (`vite` ^8.0)
- **CSS / UI**: Bootstrap 5.3 + FontAwesome + Tema Varlıkları + `$lib/theme/cards.css`
- **Etkileşim Kütüphaneleri**: Swiper 14, Fancyapps UI 6 (SSR uyumlu dinamik import)
- **Backend API Proxy**: `vite.config.ts` üzerinden geliştirme ortamında Docker nginx proxy'sine yönlendirilir (`host: shopera.test`, `target: http://127.0.0.1:80`).

---

## 2. Svelte 5 Runes Standartları (ÇOK ÖNEMLİ)

Projenin `vite.config.ts` dosyasında `runes: true` açıkça tanımlanmıştır. Kod tabanında Svelte 4 / legacy sözdizimi kesinlikle kullanılmamalıdır.

### 2.1. Eski (Svelte 4) vs Yeni (Svelte 5) Karşılaştırması

| Özellik | ❌ Svelte 4 (KULLANMA) | ✅ Svelte 5 Runes (ZORUNLU) |
| :--- | :--- | :--- |
| **Props Tanımı** | `export let product;` | `let { product }: { product: ApiProduct } = $props();` |
| **Varsayılan Değerli Props** | `export let perPage = 12;` | `let { perPage = 12 }: { perPage?: number } = $props();` |
| **İki Yönlü Bindable Prop** | `export let open = false;` | `let { open = $bindable(false) } = $props();` |
| **Reaktif Durum (State)** | `let count = 0;` | `let count = $state(0);` |
| **Nesne/Dizi Durumu** | `let list = []; list.push(x); list = list;` | `let list = $state<string[]>([]); list.push(x);` (otomatik reaktif) |
| **Hesaplanan Değer (Derived)** | `$: discount = price - offer;` | `const discount = $derived(price - offer);` |
| **Karmaşık Hesaplama** | `$: result = compute(a, b);` | `const result = $derived.by(() => { ... return val; });` |
| **Yan Etki (Effect)** | `$: if (open) focus();` | `$effect(() => { if (open) focus(); });` |
| **DOM Olay Dinleyicileri** | `<button on:click={fn}>` | `<button onclick={fn}>` (HTML standart isimleri) |
| **Form Olayları** | `<form on:submit|preventDefault={fn}>` | `<form onsubmit={(e) => { e.preventDefault(); fn(); }}>` |
| **İçerik Yerleşimi (Slot)** | `<slot />` | `let { children } = $props();` ve `{@render children()}` |
| **Özel Parça (Snippet)** | Named slots `<slot name="header" />` | `{#snippet header()}...{/snippet}` ve `{@render header()}` |

### 2.2. Olay (Event) Yakalama Kuralı
Svelte 5'te `on:click`, `on:change`, `on:keydown` gibi kolonlu sözdizimleri kaldırılmıştır:
- Tıklama: `onclick={(e) => handleClick(e)}`
- Seçim: `onchange={(e) => handleChange(e)}`
- Giriş: `oninput={(e) => handleInput(e)}`
- Klavye: `onkeydown={(e) => handleKeyDown(e)}`

---

## 3. Kod Düzeni ve Dizin Hiyerarşisi

```
src/
├── lib/
│   ├── api.ts              # Merkezi fetch sarmalayıcısı (apiGet, apiGetWithMeta, buildQuery)
│   ├── products.ts         # ApiProduct tipi, fiyat/görsel hesaplayıcılar, fetchProducts
│   ├── categories.ts       # ApiCategory tipi, hiyerarşik ağaç (buildTree), yerelleştirme (categoryName)
│   ├── filters.ts          # Dinamik kategori filtreleri (fetchCategoryFilters, FilterDefinition)
│   ├── shop.ts             # Mağaza filtreleme (fetchShopProducts, fetchShopFilters, facet tipleri)
│   ├── theme/
│   │   ├── behaviors.ts    # jQuery gerektirmeyen native nice-select, sayaç ve DOM yönetimi
│   │   └── cards.css       # Kart bileşenlerine özel CSS kuralları
│   └── components/
│       ├── cards/          # Tekil kartlar (ShopProductCard, BestSellerCard, FeaturedProductCard)
│       ├── layout/         # Genel iskelet (SiteHeader, SiteFooter, NavMenu, Offcanvas, Preloader, BackToTop)
│       ├── pages/          # Sayfa bazlı modüler bölümler (home/, shop/, cart/, checkout/ vb.)
│       └── shop/           # Mağazaya özel bileşenler (DynamicFilter.svelte vb.)
└── routes/
    ├── +layout.svelte      # Ana layout (CSS yüklemeleri, Fancybox init, header/footer)
    ├── +page.svelte        # Ana sayfa
    ├── shop/               # Mağaza ürün listeleme ve detay sayfaları
    ├── cart/               # Sepet
    ├── checkout/           # Ödeme adımları
    ├── wishlist/           # İstek listesi
    ├── login/ / register/  # Kimlik doğrulama
    └── dashboard/          # Kullanıcı paneli
```

---

## 4. API Entegrasyon Prensip ve Kalıpları

### 4.1. `$lib/api.ts` Kullanımı
ShopEra backend'i tüm cevapları `{ success, message, data, meta }` formatında döndürür.
Frontend içinde doğrudan `fetch` yazılmamalı, daima `$lib/api.ts` fonksiyonları kullanılmalıdır:

```typescript
import { apiGet, apiGetWithMeta } from '$lib/api';
import type { ApiProduct } from '$lib/products';

// 1. Doğrudan data dizisini veya nesnesini almak için:
export async function getProducts(): Promise<ApiProduct[]> {
    const data = await apiGet<ApiProduct[]>('/product', { per_page: 12 });
    return Array.isArray(data) ? data : [];
}

// 2. Sayfalama meta bilgisiyle birlikte almak için:
export async function getProductsWithPagination(page: number) {
    const { data, meta } = await apiGetWithMeta<ApiProduct[]>('/product', { page, per_page: 24 });
    return { items: data ?? [], meta };
}
```

### 4.2. Dizi Parametrelerinin Serialize Edilmesi
Laravel API'leri dizi sorgu parametrelerini `key[]=val1&key[]=val2` olarak bekler.
`$lib/api.ts` içerisindeki `buildQuery()` fonksiyonu dizi değerleri tespit ettiğinde otomatik olarak `append('${key}[]', ...)` işlemi yapar. Ekstra manuel URL manipülasyonu yapmayın.

### 4.3. Çok Dilli Alanlar
Backend'den dönen verilerde (özellikle kategorilerde) `name` alanı hem düz metin hem de `{ az: "...", en: "...", ru: "..." }` sözlüğü olarak dönebilir.
Bu alanları ekrana basarken daima yardımcı fonksiyonlar kullanın:
```typescript
import { categoryName } from '$lib/categories';
// Şablon içinde:
<span>{categoryName(category, 'az')}</span>
```

---

## 5. SSR (Server-Side Rendering) ve Harici Kütüphaneler

SvelteKit bileşenleri sunucu tarafında da çalıştırılır. Dolayısıyla `window`, `document` veya tarayıcıya özgü nesnelere doğrudan erişilemez:

### 5.1. Dinamik Kütüphane Yükleme
`@fancyapps/ui` gibi kütüphaneler dosya başında `import ... from ...` şeklinde yüklenirse SSR aşamasında `window is not defined` hatası verir.
Bu tür kütüphaneler daima **`onMount` içinde dinamik `import()`** ile yüklenmelidir:

```typescript
import { onMount } from 'svelte';

onMount(() => {
    let dispose: (() => void) | undefined;
    (async () => {
        const { Fancybox } = await import('@fancyapps/ui');
        Fancybox.bind('.popup-video, .img-popup');
        dispose = () => Fancybox.destroy();
    })();

    return () => dispose?.();
});
```

### 5.2. Tema Davranışları (`$lib/theme/behaviors.ts`)
- Orijinal şablonun jQuery bağımlılıkları ortadan kaldırılmış ve `initPageBehaviors()` altında saf JavaScript ile toplanmıştır.
- Özel `<select class="single-select">` elemanları `nice-select` widget'ına dönüştürülür.
- Dinamik olarak yeni select elemanları yüklendiğinde (örn. kategori değişiminde dinamik filtreler geldiğinde), `initPageBehaviors()` tekrar çağrılmalıdır.
- Sayfa geçişlerinde `$app/navigation` altındaki `afterNavigate()` kancası kullanılarak DOM maskeleri ve arka plan görselleri güncellenmelidir.

---

## 6. TypeScript ve Tip Bütünlüğü Kuralları

1. **Katı Tipler Tanımlayın**: Her model için (`Product`, `Order`, `User`, `CartItem`) açık arayüzler tanımlayın.
2. **Opsiyonel Alanları Güvenle Ele Alın**:
   ```typescript
   // ❌ Hata potansiyeli:
   const price = product.discount.toFixed(2);
   // ✅ Güvenli kullanım:
   const price = Number(product.discount || product.price || 0).toFixed(2);
   ```
3. **Null Kontrollerini Atlamayın**: DOM sorgularında (`closest`, `querySelector`) dönen değerlerin `null` olabileceğini hesaba katın:
   ```typescript
   const li = target.closest('li');
   if (li) {
       li.classList.toggle('open');
   }
   ```
4. **svelte-check Doğrulaması**:
   Herhangi bir frontend değişikliğinden sonra mutlaka şu komutu çalıştırarak tip ve derleme denetimi yapın:
   ```bash
   npm run check
   ```

---

## 7. Bileşen Geliştirme Şablonu

Yeni bir Svelte 5 bileşeni yazarken aşağıdaki iskeleti referans alın:

```svelte
<script lang="ts">
    import type { ApiProduct } from '$lib/products';
    import { productImage, productTitle, hasDiscount } from '$lib/products';

    // Props tanımı (Svelte 5 Runes)
    let { 
        product, 
        compact = false,
        onselect
    }: { 
        product: ApiProduct; 
        compact?: boolean;
        onselect?: (product: ApiProduct) => void;
    } = $props();

    // Reaktif yerel durum
    let isHovered = $state(false);

    // Türetilmiş reaktif değer
    const badgeText = $derived(hasDiscount(product) ? 'İndirim' : null);

    function handleClick() {
        if (onselect) onselect(product);
    }
</script>

<div 
    class="product-card" 
    class:compact 
    onmouseenter={() => isHovered = true} 
    onmouseleave={() => isHovered = false}
>
    {#if badgeText}
        <span class="badge">{badgeText}</span>
    {/if}

    <img src={productImage(product)} alt={productTitle(product)} loading="lazy" />

    <div class="product-details">
        <h6 class="title">{productTitle(product)}</h6>
        <button type="button" class="btn btn-sm btn-primary" onclick={handleClick}>
            İncele
        </button>
    </div>
</div>

<style>
    .product-card {
        transition: transform 0.2s ease-in-out;
    }
    .compact {
        padding: 0.5rem;
    }
</style>
```

---

## 8. Performans ve Güvenlik Hatırlatmaları

- Görsellerde daima `loading="lazy"` kullanın ve `$lib/products.ts:FALLBACK_IMAGE` varsayılan görselini hazır tutun.
- Büyük döngülerde `{#each list as item (item.id)}` formatında benzersiz bir anahtar (`key`) verin.
- Hassas API anahtarlarını client-side koduna (`resources/frontend/src/`) gömmeyin; yalnızca `VITE_` önekli genel ortam değişkenlerini kullanın.
