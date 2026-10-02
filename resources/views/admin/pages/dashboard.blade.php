@extends('admin.layouts.app')

@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <a href="{{ $stat['route'] }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-gray-800">{{ number_format($stat['value']) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm lg:col-span-1">
            <p class="text-sm text-gray-500">{{ $monthLabel }} gəliri</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ number_format($revenue, 2) }} ₼</p>
            <p class="mt-1 text-xs text-gray-400">Ödənilmiş / emal olunan sifarişlər əsasında</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <p class="mb-3 text-sm font-semibold text-gray-700">Sifariş statusları</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($statusCards as $card)
                    <a href="{{ route('admin.orders.index', ['status' => $card['value']]) }}"
                       class="flex min-w-28 flex-1 flex-col rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 hover:border-brand-200 hover:bg-brand-50">
                        <span class="text-xs text-gray-500">{{ $card['label'] }}</span>
                        <span class="text-lg font-bold text-gray-800">{{ $card['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                <p class="font-semibold text-gray-700">Son sifarişlər</p>
                <a href="{{ route('admin.orders.index') }}" class="text-sm text-brand-600 hover:underline">Hamısı</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase text-gray-400">
                            <th class="px-5 py-2 font-semibold">#</th>
                            <th class="px-5 py-2 font-semibold">Müştəri</th>
                            <th class="px-5 py-2 font-semibold">Məbləğ</th>
                            <th class="px-5 py-2 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($recentOrders as $order)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-2.5">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="font-medium text-brand-600 hover:underline">#{{ $order->id }}</a>
                                </td>
                                <td class="px-5 py-2.5">{{ $order->user?->name }} {{ $order->user?->surname }}</td>
                                <td class="px-5 py-2.5 font-medium">{{ number_format((float) $order->total_price, 2) }} ₼</td>
                                <td class="px-5 py-2.5">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600">
                                        {{ $order->latestStatus?->label ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Sifariş yoxdur</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3">
                <p class="font-semibold text-gray-700">Ən çox satılanlar</p>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse ($topProducts as $item)
                    <div class="flex items-center gap-3 px-5 py-3">
                        @if ($item->product?->images->first())
                            <img src="{{ $item->product->images->first()->image_path }}" class="h-10 w-10 rounded-lg object-cover ring-1 ring-gray-200">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-300">—</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-gray-700">{{ $item->product?->title ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $item->total_sold }} satış</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-gray-400">Məlumat yoxdur</p>
                @endforelse
            </div>
            @if ($pendingReviews > 0)
                <div class="border-t border-gray-100 px-5 py-3">
                    <a href="{{ route('admin.reviews.index', ['status' => 0]) }}" class="text-sm text-amber-600 hover:underline">
                        {{ $pendingReviews }} şərh gözləyir
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
