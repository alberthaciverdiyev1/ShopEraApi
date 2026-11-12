@extends('admin.layouts.app')

@section('content')
    @php
        $statIcons = [
            'box' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
            'receipt' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
            'users' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
            'layers' => 'M6.429 9.75L2.25 12l4.179 2.25m12.642 0L21.75 12l-4.179-2.25m-12.642 0l6.321-3.402a2.25 2.25 0 012.142 0l6.321 3.402a2.25 2.25 0 010 0l.001 0M6.429 9.75l6.321-3.402a2.25 2.25 0 012.142 0l6.321 3.402m-14.784 0l6.321 3.402a2.25 2.25 0 002.142 0l6.321-3.402',
        ];

        $statusTones = [
            0 => ['dot' => 'bg-amber-500', 'bg' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'],
            1 => ['dot' => 'bg-blue-500', 'bg' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'],
            2 => ['dot' => 'bg-emerald-500', 'bg' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'],
            3 => ['dot' => 'bg-slate-400', 'bg' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'],
            4 => ['dot' => 'bg-orange-500', 'bg' => 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300'],
            5 => ['dot' => 'bg-rose-500', 'bg' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'],
            6 => ['dot' => 'bg-rose-500', 'bg' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'],
            7 => ['dot' => 'bg-violet-500', 'bg' => 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300'],
        ];

        $statusTone = fn (int $value) => $statusTones[$value] ?? ['dot' => 'bg-slate-400', 'bg' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'];
    @endphp

    {{-- Quick actions --}}
    <div class="mb-5 flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.products.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Yeni məhsul
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 4]) }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
            Gözləyən ödənişlər
        </a>
        @if ($pendingReviews > 0)
            <a href="{{ route('admin.reviews.index', ['status' => 0]) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2 text-sm font-medium text-amber-700 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-500 px-1 text-[11px] font-bold text-white">{{ $pendingReviews }}</span>
                Şərh gözləyir
            </a>
        @endif
        <a href="{{ route('admin.notifications.index') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
            Bildiriş göndər
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <a href="{{ $stat['route'] }}"
               class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/40">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-slate-800 dark:text-white">{{ number_format($stat['value']) }}</p>
                    </div>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-600 group-hover:text-white dark:bg-indigo-500/10 dark:text-indigo-300">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $statIcons[$stat['icon']] ?? $statIcons['box'] }}"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 flex items-center gap-1 text-xs font-medium text-slate-400 group-hover:text-indigo-600 dark:text-slate-500 dark:group-hover:text-indigo-300">
                    Ətraflı bax
                    <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </p>
            </a>
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-600 to-violet-700 p-5 text-white shadow-sm lg:col-span-1">
            <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-10 -right-2 h-24 w-24 rounded-full bg-white/5"></div>
            <p class="text-sm font-medium text-indigo-100">{{ $monthLabel }} gəliri</p>
            <p class="mt-2 text-3xl font-bold tracking-tight">{{ number_format($revenue, 2) }} ₼</p>
            <p class="mt-2 text-xs leading-relaxed text-indigo-100/80">Ödənilmiş və emal olunan sifarişlər əsasında hesablanır.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Sifariş statusları</p>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400">Hamısı</a>
            </div>
            <div class="grid grid-cols-1 gap-2 p-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($statusCards as $card)
                    @php $tone = $statusTone((int) $card['value']); @endphp
                    <a href="{{ route('admin.orders.index', ['status' => $card['value']]) }}"
                       class="flex items-center gap-2.5 rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2.5 transition hover:border-indigo-200 hover:bg-white dark:border-slate-800 dark:bg-slate-800/50 dark:hover:border-indigo-500/40">
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $tone['dot'] }}"></span>
                        <span class="min-w-0 flex-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $card['label'] }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $tone['bg'] }}">{{ $card['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Son sifarişlər</p>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400">Hamısı</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-[11px] uppercase tracking-wider text-slate-400 dark:border-slate-800 dark:text-slate-500">
                            <th class="px-5 py-2.5 font-semibold">#</th>
                            <th class="px-5 py-2.5 font-semibold">Müştəri</th>
                            <th class="px-5 py-2.5 font-semibold">Məbləğ</th>
                            <th class="px-5 py-2.5 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse ($recentOrders as $order)
                            <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">#{{ $order->id }}</a>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ strtoupper(substr($order->user?->name ?? '?', 0, 1)) }}
                                        </span>
                                        <span class="truncate text-slate-700 dark:text-slate-300">{{ $order->user?->name }} {{ $order->user?->surname }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 font-medium text-slate-800 dark:text-slate-200">{{ number_format((float) $order->total_price, 2) }} ₼</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $order->latestStatus?->label ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">Hələ sifariş yoxdur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Ən çox satılanlar</p>
                <span class="text-[11px] text-slate-400">{{ $monthLabel }}</span>
            </div>
            <div class="divide-y divide-slate-50 dark:divide-slate-800">
                @forelse ($topProducts as $index => $item)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <span class="w-4 text-center text-xs font-bold text-slate-400">{{ $index + 1 }}</span>
                        @if ($item->product?->images->first())
                            <img src="{{ $item->product->images->first()->image_path }}" alt="" class="h-10 w-10 rounded-lg object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-300 dark:bg-slate-800">—</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-slate-700 dark:text-slate-300">{{ $item->product?->title ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $item->total_sold }} satış</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-400">Bu ay satış olmayıb.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
