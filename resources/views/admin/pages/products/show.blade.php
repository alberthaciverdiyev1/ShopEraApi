@extends('admin.layouts.app')

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-gray-200 bg-white p-2 text-gray-500 hover:bg-gray-50">@include('admin.partials.icon', ['name' => 'back', 'class' => 'h-4 w-4'])</a>
        <h2 class="text-lg font-semibold text-gray-800">{{ $product->title ?? '#' . $product->id }}</h2>
        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $product->is_active ? 'Aktiv' : 'Deaktiv' }}</span>
        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">{{ $product->approval_status }}</span>
        <a href="{{ route('admin.products.edit', $product->id) }}" class="ml-auto rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Redaktə et</a>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Şəkillər ({{ $product->images->count() }})</p>
                <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                    @forelse ($product->images as $image)
                        <div class="group relative">
                            <img src="{{ $image->image_path }}" class="aspect-square w-full rounded-xl object-cover ring-1 ring-gray-200">
                            <form method="POST" action="{{ route('admin.products.images.destroy', $image->id) }}"
                                  class="absolute right-1.5 top-1.5 opacity-0 transition group-hover:opacity-100">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-600/90 p-1.5 text-white" onclick="return confirm('Şəkil silinsin?')">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="col-span-full py-6 text-center text-sm text-gray-400">Şəkil yoxdur</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Videolar ({{ $product->videos->count() }})</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @forelse ($product->videos as $video)
                        <div class="group relative">
                            <video src="{{ $video->video_path }}" class="aspect-video w-full rounded-xl bg-gray-900 object-cover" muted controls></video>
                            <form method="POST" action="{{ route('admin.products.videos.destroy', $video->id) }}" class="absolute right-1.5 top-1.5 opacity-0 transition group-hover:opacity-100">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-rose-600/90 p-1.5 text-white" onclick="return confirm('Video silinsin?')">@include('admin.partials.icon', ['name' => 'x', 'class' => 'h-4 w-4'])</button>
                            </form>
                        </div>
                    @empty
                        <p class="col-span-full py-6 text-center text-sm text-gray-400">Video yoxdur</p>
                    @endforelse
                </div>
            </div>

            @php
                // CJ descriptions are HTML. Allow only formatting tags and strip
                // every attribute so no scripts / event handlers can run.
                $rawDescription = (string) ($product->description ?? '');
                $safeDescription = trim((string) preg_replace(
                    '/<(\w+)[^>]*>/',
                    '<$1>',
                    strip_tags($rawDescription, '<p><br><strong><em><b><i><u><ul><ol><li><h2><h3><h4><blockquote>'),
                ));
            @endphp
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Təsvir</p>
                @if ($safeDescription !== '')
                    <div class="text-sm leading-relaxed text-gray-600 [&_blockquote]:border-l-2 [&_blockquote]:border-gray-200 [&_blockquote]:pl-3 [&_li]:ml-4 [&_ol]:list-decimal [&_p]:mb-2 [&_ul]:list-disc">{!! $safeDescription !!}</div>
                @else
                    <p class="text-sm text-gray-400">—</p>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Şərhlər ({{ $product->reviews_count }})</p>
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-bold text-gray-800">{{ number_format((float) $product->reviews_avg_rate, 1) }}</span>
                    <span class="inline-flex">
                        @for ($i = 1; $i <= 5; $i++)
                            @include('admin.partials.icon', ['name' => $i <= (int) round($product->reviews_avg_rate) ? 'star-solid' : 'star', 'class' => 'h-4 w-4 '.($i <= (int) round($product->reviews_avg_rate) ? 'text-amber-500' : 'text-gray-300')])
                        @endfor
                    </span>
                    <a href="{{ route('admin.reviews.index', ['q' => '']) }}" class="ml-auto text-sm text-brand-600 hover:underline">Şərhlərə bax</a>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Məlumat</p>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Qiymət</dt><dd class="font-semibold text-gray-800">{{ number_format((float) $product->price, 2) }} ₼</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Endirim</dt><dd class="text-gray-700">{{ $product->discount ? number_format((float) $product->discount, 2).' ₼' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Stok</dt><dd class="text-gray-700">{{ $product->stock_count }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Baxış</dt><dd class="text-gray-700">{{ $product->views }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">SKU</dt><dd class="text-gray-700">{{ $product->sku ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Kateqoriya</dt><dd class="text-gray-700">{{ $product->category?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Brend</dt><dd class="text-gray-700">{{ $product->brand?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Cins</dt><dd class="text-gray-700">{{ $product->gender ?? '—' }}</dd></div>
                </dl>
            </div>

            @if ($dropshipping)
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="font-semibold text-gray-700">CJ Dropshipping</p>
                        @if ($dropshipping->product_type)
                            <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600">{{ $dropshipping->product_type }}</span>
                        @endif
                    </div>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="shrink-0 text-gray-500">CJ pid</dt><dd class="truncate font-mono text-xs text-gray-700" title="{{ $dropshipping->cj_product_id }}">{{ $dropshipping->cj_product_id }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">CJ SKU</dt><dd class="text-gray-700">{{ $dropshipping->cj_sku ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Təchizatçı</dt><dd class="text-gray-700">{{ $dropshipping->supplier_name ?? $dropshipping->supplier_id ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Kateqoriya (CJ)</dt><dd class="truncate text-gray-700" title="{{ $dropshipping->cj_category_name }}">{{ $dropshipping->cj_category_name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Anbar</dt><dd class="text-gray-700">{{ $dropshipping->warehouse ?? '—' }}{{ $dropshipping->area_country_code ? ' ('.$dropshipping->area_country_code.')' : '' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Göndərmə</dt><dd class="text-gray-700">{{ $dropshipping->shop_method ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">CJ qiymət</dt><dd class="text-gray-700">{{ $dropshipping->cj_price ?? '—' }}</dd></div>
                        @if ($dropshipping->cj_discount_price)
                            <div class="flex justify-between"><dt class="text-gray-500">CJ endirim</dt><dd class="text-gray-700">{{ number_format((float) $dropshipping->cj_discount_price, 2) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-gray-500">Çəki (q)</dt><dd class="text-gray-700">{{ $dropshipping->weight_grams ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Paket çəkisi (q)</dt><dd class="text-gray-700">{{ $dropshipping->pack_weight_grams ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Variant sayı</dt><dd class="text-gray-700">{{ is_array($dropshipping->variants) ? count($dropshipping->variants) : 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Listed</dt><dd class="text-gray-700">{{ $dropshipping->listed_num }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Satış statusu</dt><dd class="text-gray-700">{{ $dropshipping->sale_status ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Pulsuz göndərmə</dt><dd class="text-gray-700">{{ $dropshipping->is_free_shipping ? 'Bəli' : 'Xeyr' }}</dd></div>
                        @if (! empty($dropshipping->shipping_country_codes))
                            <div class="flex justify-between gap-3"><dt class="shrink-0 text-gray-500">Ölkələr</dt><dd class="truncate text-right text-gray-700" title="{{ implode(', ', (array) $dropshipping->shipping_country_codes) }}">{{ implode(', ', (array) $dropshipping->shipping_country_codes) }}</dd></div>
                        @endif
                    </dl>
                </div>
            @endif

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Rənglər</p>
                <div class="flex flex-wrap gap-2">
                    @forelse ($product->colors as $color)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-2.5 py-1 text-xs">
                            <span class="h-3.5 w-3.5 rounded-full ring-1 ring-gray-200" style="background: {{ $color->hex }}"></span>
                            {{ $color->name }}
                        </span>
                    @empty
                        <p class="text-sm text-gray-400">—</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Ölçülər</p>
                <div class="flex flex-wrap gap-2">
                    @forelse ($product->sizes as $size)
                        <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs text-gray-700" title="Qiymət: {{ $size->pivot->price }}">
                            {{ $size->name }}
                        </span>
                    @empty
                        <p class="text-sm text-gray-400">—</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
