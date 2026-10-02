@php
    $tone = [
        0 => 'bg-blue-50 text-blue-700', 1 => 'bg-amber-50 text-amber-700', 2 => 'bg-emerald-50 text-emerald-700',
        3 => 'bg-orange-50 text-orange-700', 4 => 'bg-gray-100 text-gray-600', 5 => 'bg-rose-50 text-rose-700',
        6 => 'bg-rose-50 text-rose-700', 7 => 'bg-purple-50 text-purple-700',
    ];
@endphp
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                <th class="px-4 py-3 font-semibold">Sifariş</th>
                <th class="px-4 py-3 font-semibold">Müştəri</th>
                <th class="px-4 py-3 font-semibold">Məbləğ</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold">Tarix</th>
                <th class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($rows as $order)
                @php $status = $order->latestStatus?->status; @endphp
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3 font-medium text-gray-700">#{{ $order->id }}</td>
                    <td class="px-4 py-3">
                        <p class="text-gray-700">{{ $order->user?->name }} {{ $order->user?->surname }}</p>
                        <p class="text-xs text-gray-400">{{ $order->user?->phone }}</p>
                    </td>
                    <td class="px-4 py-3 font-medium">{{ number_format((float) $order->total_price, 2) }} ₼</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $tone[$status?->value ?? -1] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $status?->label() ?? '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-1">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="rounded-lg p-2 text-gray-500 hover:bg-brand-50 hover:text-brand-600" title="Bax">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('admin.orders.destroy', $order->id) }}">
                                @csrf @method('DELETE')
                                <button class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600" title="Sil" onclick="return confirm('Silinsin?')">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Sifariş yoxdur</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('admin.partials.pagination', ['rows' => $rows])
