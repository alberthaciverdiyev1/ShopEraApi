<x-storefront.layouts.app :title="'ShopEra — Alqı-satqı elanları və mağazalar'" :categories="$categories" :popup="$popup">
    <x-storefront.category-strip :categories="$categories" />
    <x-storefront.home.featured-products :products="$featuredProducts" />
    <x-storefront.home.premium-listings :products="$premiumListings" />
</x-storefront.layouts.app>
