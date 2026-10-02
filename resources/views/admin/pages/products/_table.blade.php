@php
    $approvalTone = ['approved' => 'bg-emerald-50 text-emerald-700', 'pending' => 'bg-amber-50 text-amber-700', 'rejected' => 'bg-rose-50 text-rose-700'];
@endphp
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                <th class="px-4 py-3 font-semibold">Məhsul</th>
                <th class="px-4 py-3 font-semibold">Kateqoriya</th>
                <th class="px-4 py-3 font-semibold">Qiymət</th>
                <th class="px-4 py-3 font-semibold">Stok</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($rows as $product)
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            @if ($product->images->first())
                                <img src="{{ $product->images->first()->image_path }}" class="h-10 w-10 rounded-lg object-cover ring-1 ring-gray-200">
                            @else
                                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-300">—</span>
                            @endif
                            <div class="min-w-0">
                                <a href="{{ route('admin.products.show', $product->id) }}" class="line-clamp-1 font-medium text-gray-700 hover:text-brand-600">{{ $product->title ?? '—' }}</a>
                                <p class="text-xs text-gray-400">#{{ $product->id }}{{ $product->sku ? ' · '.$product->sku : '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $product->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium">{{ number_format((float) $product->price, 2) }} ₼</td>
                    <td class="px-4 py-3">
                        <span class="{{ (int) $product->stock_count <= 0 ? 'text-rose-600' : 'text-gray-600' }}">{{ $product->stock_count }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-col gap-1">
                            <span class="w-fit rounded-full px-2 py-0.5 text-xs font-medium {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $product->is_active ? 'Aktiv' : 'Deaktiv' }}
                            </span>
                            <span class="w-fit rounded-full px-2 py-0.5 text-xs font-medium {{ $approvalTone[$product->approval_status] ?? 'bg-gray-100 text-gray-500' }}">
                                {{ $product->approval_status }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-1">
                            <a href="{{ route('admin.products.show', $product->id) }}" class="rounded-lg p-2 text-gray-500 hover:bg-brand-50 hover:text-brand-600" title="Bax">@include('admin.partials.icon', ['name' => 'eye', 'class' => 'h-4 w-4'])</a>
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="rounded-lg p-2 text-gray-500 hover:bg-brand-50 hover:text-brand-600" title="Redaktə">@include('admin.partials.icon', ['name' => 'pencil', 'class' => 'h-4 w-4'])</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}">
                                @csrf @method('DELETE')
                                <button class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600" onclick="return confirm('Məhsul silinsin?')">@include('admin.partials.icon', ['name' => 'trash', 'class' => 'h-4 w-4'])</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Məhsul yoxdur</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('admin.partials.pagination', ['rows' => $rows])
