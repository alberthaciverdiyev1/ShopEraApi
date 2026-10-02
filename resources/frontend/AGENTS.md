# AGENTS.md (Frontend Scope)

Bu dosya `resources/frontend/` dizini kapsamındaki alt ajanlar (subagents) için hızlı başvuru özetidir.
Detaylı kılavuz için lütfen [`GEMINI.md`](file:///home/albert/Workspace/FoxSoft/ShopEra/resources/frontend/GEMINI.md) dosyasına bakın.

## Temel Kurallar
1. **Svelte 5 Runes Zorunludur**: `$state`, `$derived`, `$derived.by`, `$props`, `$effect`. Svelte 4 sözdizimi kullanılamaz.
2. **Event Handlers**: `onclick`, `onsubmit`, `onchange` (kolon `on:` yok).
3. **API İstemcisi**: Doğrudan fetch yerine `$lib/api.ts` (`apiGet`, `apiGetWithMeta`) kullanılmalıdır.
4. **SSR Uyumlu Kod**: DOM nesnelerine erişen kütüphaneler (`@fancyapps/ui`, `swiper`) `onMount` içinde dinamik `import()` ile yüklenir.
5. **Tip Denetimi**: Kod yazımı bittikten sonra `npm run check` ile hatalar doğrulanmalıdır.
