@extends('admin.layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.listing-reports.index') }}" class="rounded-lg border px-3 py-2 text-sm {{ !$status ? 'bg-gray-900 text-white' : 'border-gray-200 text-gray-600' }}">Hamısı</a>
            <a href="{{ route('admin.listing-reports.index', ['status' => 'new']) }}" class="rounded-lg border px-3 py-2 text-sm {{ $status === 'new' ? 'bg-gray-900 text-white' : 'border-gray-200 text-gray-600' }}">Yeni</a>
            <a href="{{ route('admin.listing-reports.index', ['status' => 'resolved']) }}" class="rounded-lg border px-3 py-2 text-sm {{ $status === 'resolved' ? 'bg-gray-900 text-white' : 'border-gray-200 text-gray-600' }}">Həll edilmiş</a>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700">
                            <th class="px-4 py-3 font-semibold">Elan</th>
                            <th class="px-4 py-3 font-semibold">Səbəb</th>
                            <th class="px-4 py-3 font-semibold">Kim</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        @forelse ($reports as $report)
                            <tr>
                                <td class="px-4 py-3">
                                    @if ($report->product)
                                        <a href="{{ route('admin.products.show', $report->product->id) }}" class="font-medium text-brand-600 hover:underline">{{ admin_label($report->product, 'title', '#'.$report->product_id) }}</a>
                                    @else
                                        <span class="text-gray-400">#{{ $report->product_id }}</span>
                                    @endif
                                    @if ($report->comment)<p class="mt-1 text-xs text-gray-500">{{ $report->comment }}</p>@endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $report->reason }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $report->user?->email ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($report->status === 'resolved')
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Həll edildi</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Yeni</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($report->status !== 'resolved')
                                        <form method="POST" action="{{ route('admin.listing-reports.resolve', $report->id) }}">
                                            @csrf @method('PUT')
                                            <button class="rounded-lg bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-900">Həll et</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Şikayət yoxdur</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @include('admin.partials.pagination', ['rows' => $reports])
    </div>
@endsection
