@extends('admin.layouts.app')

@php
    $input = 'w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white';
    $labelCls = 'mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300';
@endphp

@section('content')
    <div class="mx-auto max-w-4xl space-y-5">
        {{-- Packages --}}
        <form method="POST" action="{{ route('admin.promotions.storePackage') }}"
              class="rounded-lg border border-dashed border-gray-300 bg-white p-4 dark:border-gray-600 dark:bg-gray-800">
            @csrf
            <p class="mb-3 font-semibold text-gray-800 dark:text-white">Yeni paket</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-5">
                <div class="sm:col-span-2">
                    <label class="{{ $labelCls }}">Ad</label>
                    <input name="name" required placeholder="Məsələn: 7 gün irəli" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Növ</label>
                    <select name="type" class="{{ $input }}">
                        <option value="promoted">İrəli çəkilmiş</option>
                        <option value="vip">VIP</option>
                        <option value="premium">Premium</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $labelCls }}">Gün</label>
                    <input name="days" type="number" min="1" value="7" required class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Qiymət (₼)</label>
                    <input name="price" type="number" min="0" step="0.01" value="0" required class="{{ $input }}">
                </div>
            </div>
            <div class="mt-3">
                <label class="{{ $labelCls }}">Təsvir (modalda göstərilir)</label>
                <textarea name="description" rows="2" class="{{ $input }}" placeholder="Elan bütün və axtarış nəticələrinin içində birinci yerə qalxacaq."></textarea>
            </div>
            <div class="mt-3">
                <label class="{{ $labelCls }}">Bonus (modalda göstərilir, opsional)</label>
                <input name="bonus" class="{{ $input }}" placeholder="14 Oktyabr 2026, 17:09 tarixinə kimi ödənilib">
            </div>
            <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-gray-300 text-brand-600"> Aktiv
            </label>
            <button class="ml-4 rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">Əlavə et</button>
        </form>

        {{-- Existing packages --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="border-b border-gray-100 px-4 py-3 font-semibold text-gray-800 dark:border-gray-700 dark:text-white">Paketlər</p>
            <div class="divide-y divide-gray-50 dark:divide-gray-700">
                @forelse ($packages as $package)
                    <form method="POST" action="{{ route('admin.promotions.updatePackage', $package->id) }}" class="flex flex-wrap items-end gap-2 px-4 py-3">
                        @csrf @method('PUT')
                        <input name="name" value="{{ admin_label($package, 'name') }}" required class="{{ $input }} min-w-40 flex-1">
                        <select name="type" class="{{ $input }} w-40">
                            <option value="promoted" @selected($package->type === 'promoted')>İrəli çəkilmiş</option>
                            <option value="vip" @selected($package->type === 'vip')>VIP</option>
                            <option value="premium" @selected($package->type === 'premium')>Premium</option>
                        </select>
                        <input name="days" type="number" min="1" value="{{ $package->days }}" class="{{ $input }} w-20">
                        <input name="price" type="number" min="0" step="0.01" value="{{ $package->price }}" class="{{ $input }} w-28">
                        <label class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-300">
                            <input type="checkbox" name="is_active" value="1" @checked($package->is_active) class="h-4 w-4 rounded border-gray-300 text-brand-600"> Aktiv
                        </label>
                        <div class="w-full">
                            <label class="{{ $labelCls }}">Təsvir (modalda göstərilir)</label>
                            <textarea name="description" rows="2" class="{{ $input }}">{{ admin_label($package, 'description', '') }}</textarea>
                        </div>
                        <div class="w-full">
                            <label class="{{ $labelCls }}">Bonus (modalda göstərilir, opsional)</label>
                            <input name="bonus" value="{{ admin_label($package, 'bonus', '') }}" class="{{ $input }}">
                        </div>
                        <button class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900">Yadda saxla</button>
                    </form>
                    <form method="POST" action="{{ route('admin.promotions.destroyPackage', $package->id) }}" class="px-4 pb-3">
                        @csrf @method('DELETE')
                        <button class="text-xs text-rose-600 hover:underline">Paketi sil</button>
                    </form>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-400">Paket yoxdur.</p>
                @endforelse
            </div>
        </div>

        {{-- Orders --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="border-b border-gray-100 px-4 py-3 font-semibold text-gray-800 dark:border-gray-700 dark:text-white">Sifarişlər</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700">
                            <th class="px-4 py-2 font-semibold">Elan</th>
                            <th class="px-4 py-2 font-semibold">Paket</th>
                            <th class="px-4 py-2 font-semibold">Qiymət</th>
                            <th class="px-4 py-2 font-semibold">Status</th>
                            <th class="px-4 py-2 text-right font-semibold">Əməliyyat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-4 py-2">
                                    @if ($order->product)
                                        <a href="{{ route('admin.products.show', $order->product->id) }}" class="font-medium text-brand-600 hover:underline">{{ admin_label($order->product, 'title', '#'.$order->product_id) }}</a>
                                    @else <span class="text-gray-400">#{{ $order->product_id }}</span> @endif
                                </td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $order->package ? admin_label($order->package, 'name') : $order->type }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ number_format((float) $order->price, 2) }} ₼</td>
                                <td class="px-4 py-2">
                                    @php $tone = ['paid' => 'emerald', 'pending' => 'amber', 'cancelled' => 'rose'][$order->status] ?? 'gray'; @endphp
                                    <span class="rounded-full bg-{{ $tone }}-50 px-2.5 py-1 text-xs font-medium text-{{ $tone }}-700">{{ $order->status }}</span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    @if ($order->status === 'pending')
                                        <form method="POST" action="{{ route('admin.promotions.activate', $order->id) }}" class="inline">
                                            @csrf @method('PUT')
                                            <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Aktiv et</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.promotions.cancel', $order->id) }}" class="inline">
                                            @csrf @method('PUT')
                                            <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">Ləğv et</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Sifariş yoxdur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
