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

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Təsvir</p>
                <p class="whitespace-pre-line text-sm text-gray-600">{{ $product->description ?: '—' }}</p>
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
