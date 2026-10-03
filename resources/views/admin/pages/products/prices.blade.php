@extends('admin.layouts.app')

@section('content')
    @php $access =  feature("bulk_price_update") @endphp
    @if($access)

        <form method="POST" action="{{ route('admin.products.prices.apply') }}"
              class="mx-auto max-w-xl space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            <h2 class="font-semibold text-gray-800">Toplu qiymət dəyişikliyi</h2>
            <p class="text-sm text-gray-500">Seçilmiş filterə uyğun bütün məhsulların qiyməti dəyişdiriləcək.</p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Əməliyyat</label>
                    <select name="type" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="increment">Artır</option>
                        <option value="decrement">Azalt</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Növ</label>
                    <select name="mode" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="percentage">Faiz (%)</option>
                        <option value="amount">Məbləğ (₼)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Dəyər</label>
                <input type="number" step="0.01" name="value" required
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Kateqoriya
                        (opsional)</label>
                    <select name="category_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="">Hamısı</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Brend (opsional)</label>
                    <select name="brand_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        <option value="">Hamısı</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button class="w-full rounded-lg bg-brand-600 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
                    onclick="return confirm('Tətbiq edilsin?')">Tətbiq et
            </button>
        </form>
    @else
        <form
            class="mx-auto max-w-xl space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            <h2 class="font-semibold text-red-500">Toplu qiymət dəyişikliyi sizin abouneliyinize daxil deyil</h2>
        </form>

    @endif
@endsection
