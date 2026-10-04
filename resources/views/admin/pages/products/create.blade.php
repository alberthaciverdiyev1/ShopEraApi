@extends('admin.layouts.app')

@section('content')
    @php
        $input = 'w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-brand-500';
    @endphp
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-700">Əsas məlumat</p>
                    <div class="space-y-4">
                        <div data-lang-tabs>
                            <div class="mb-3 flex gap-1 rounded-lg bg-gray-100 p-1">
                                @foreach ($locales as $i => $locale)
                                    <button type="button" data-lang-tab="product:{{ $locale }}"
                                            class="flex-1 rounded-md px-3 py-1.5 text-xs font-semibold uppercase {{ $i === 0 ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">{{ $locale }}</button>
                                @endforeach
                            </div>
                            @foreach ($locales as $i => $locale)
                                <div data-lang-panel="product:{{ $locale }}" @class(['hidden' => $i > 0]) class="space-y-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Başlıq ({{ $locale }})</label>
                                        <input type="text" name="title[{{ $locale }}]" value="{{ old("title.$locale") }}" @if($locale === 'az') required @endif class="{{ $input }}">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Təsvir ({{ $locale }})</label>
                                        <textarea name="description[{{ $locale }}]" rows="3" @if($locale === 'az') required @endif class="{{ $input }}">{{ old("description.$locale") }}</textarea>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('title.az')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                        @error('description.az')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if (feature('product_images'))
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="mb-4 font-semibold text-gray-700">Şəkillər</p>
                        @include('admin.partials.media-upload', ['name' => 'new_images[]', 'multiple' => true, 'accept' => 'image/*', 'label' => 'Şəkil seçin', 'hint' => 'JPG, PNG, WebP · bir neçə fayl seçə bilərsiniz'])
                    </div>
                @endif

                @if (feature('product_videos'))
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="mb-4 font-semibold text-gray-700">Videolar</p>
                        @include('admin.partials.media-upload', ['name' => 'videos[]', 'multiple' => true, 'accept' => 'video/*', 'label' => 'Video seçin', 'hint' => 'MP4 / WebM · bir neçə fayl'])
                    </div>
                @endif

                @if (feature('category_filters'))
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-700">Filtrlər</p>
                    <div id="product-filters">
                        @include('admin.pages.products._filters', ['filters' => $filters, 'productFilters' => $productFilters ?? []])
                    </div>
                </div>
                @endif


                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-700">Rənglər</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($colors as $color)
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-gray-200 px-3 py-1.5 text-sm has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input type="checkbox" name="colors[]" value="{{ $color->id }}" class="h-4 w-4 rounded border-gray-300 text-brand-600">
                                <span class="h-3.5 w-3.5 rounded-full ring-1 ring-gray-200" style="background: {{ $color->hex }}"></span>
                                {{ $color->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-700">Ölçülər və qiymətlər</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left text-xs uppercase text-gray-400">
                                    <th class="px-2 py-2">Seç</th>
                                    <th class="px-2 py-2">Ölçü</th>
                                    <th class="px-2 py-2">Qiymət</th>
                                    <th class="px-2 py-2">Endirim</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($sizes as $size)
                                    <tr>
                                        <td class="px-2 py-1.5">
                                            <input type="checkbox" name="size_ids[]" value="{{ $size->id }}" class="h-4 w-4 rounded border-gray-300 text-brand-600">
                                        </td>
                                        <td class="px-2 py-1.5">{{ admin_label($size) }}</td>
                                        <td class="px-2 py-1.5">
                                            <input type="number" step="any" name="sizes[{{ $size->id }}][price]" value="{{ old("sizes.{$size->id}.price") }}" placeholder="{{ old('price') }}" class="{{ $input }}">
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <input type="number" step="any" name="sizes[{{ $size->id }}][discount]" value="{{ old("sizes.{$size->id}.discount") }}" class="{{ $input }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Hər ölçünün öz qiyməti var. Boş buraxılan sahə məhsulun qiymətini götürür.</p>
                </div>
            </div>

            <div class="space-y-5">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-700">Qiymət və stok</p>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ([['price', 'Qiymət', 'required'], ['discount', 'Endirim', ''], ['stock_count', 'Stok', 'required'], ['weight', 'Çəki', ''], ['sku', 'SKU', ''], ['purchase_limit', 'Alış limiti', '']] as [$name, $label, $req])
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">{{ $label }}</label>
                                <input type="number" step="any" name="{{ $name }}" value="{{ old($name) }}" @if($req) required @endif class="{{ $input }}">
                            </div>
                        @endforeach
                        <div class="col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Endirim bitmə tarixi</label>
                            <input type="date" name="discount_expire_date" value="{{ old('discount_expire_date') }}" class="{{ $input }}">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">SKU boş buraxılarsa avtomatik yaradılır.</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-700">Təsnifat</p>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Kateqoriya</label>
                            <select name="main_category_id" class="{{ $input }}"
                                    hx-get="{{ route('admin.categories.children') }}" hx-target="#subcategory-wrap" hx-swap="innerHTML"
                                    hx-trigger="change" hx-include="this">
                                <option value="">— Kateqoriya seç —</option>
                                @foreach ($mainCategories as $category)
                                    <option value="{{ $category->id }}" @selected((string) $currentMain === (string) $category->id)>{{ admin_label($category) }}</option>
                                @endforeach
                            </select>
                            <label class="mb-1 mt-2 block text-xs font-semibold uppercase text-gray-400">Alt kateqoriya</label>
                            <div id="subcategory-wrap">
                                @include('admin.pages.products._subcategories', [
                                    'parent' => $mainCategories->firstWhere('id', $currentMain),
                                    'children' => $currentChildren,
                                    'current' => $currentCategory,
                                    'inputClass' => $input,
                                ])
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Brend</label>
                            <select name="brand_id" class="{{ $input }}">
                                <option value="">—</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Cins</label>
                            <select name="gender" class="{{ $input }}">
                                <option value="">—</option>
                                @foreach ($genders as $gender)
                                    <option value="{{ $gender->value }}" @selected(old('gender') === $gender->value)>{{ $gender->value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Təsdiq statusu</label>
                            <select name="approval_status" class="{{ $input }}">
                                @foreach (['approved', 'pending', 'rejected'] as $status)
                                    <option value="{{ $status }}" @selected(old('approval_status', 'approved') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-700">Status</p>
                    <div class="space-y-3">
                        @foreach ([['is_active', 'Aktivdir', true], ['is_pinned', 'Sabitlənmiş', false], ['is_suggest', 'Təklif olunur', false]] as [$name, $label, $default])
                            <label class="relative inline-flex cursor-pointer items-center">
                            <input type="hidden" name="{{ $name }}" value="0">
                            <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $default)) class="peer sr-only">
                            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-700"></div>
                            <span class="ms-3 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-700">Banner</p>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-400">Banner tipi</label>
                    <select name="banner_type" class="{{ $input }}">
                        <option value="">Banner etmə</option>
                        <option value="big" @selected(old('banner_type', $bannerType) === 'big')>Böyük (Hero)</option>
                        <option value="middle" @selected(old('banner_type', $bannerType) === 'middle')>Orta (Best seller)</option>
                        <option value="small" @selected(old('banner_type', $bannerType) === 'small')>Kiçik (Promo)</option>
                    </select>
                    <p class="mt-2 text-xs text-gray-400">Seçilərsə məhsulun şəkilləri ilə <b>aktiv</b> banner yaradılır (ilk şəkil banner, ikinci şəkil ikinci şəkil).</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Ləğv et</a>
            <button class="rounded-lg bg-brand-600 px-6 py-2 text-sm font-semibold text-white hover:bg-brand-700">Əlavə et</button>
        </div>
    </form>
@endsection
