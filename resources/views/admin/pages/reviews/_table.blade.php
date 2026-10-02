@php
    $tones = [0 => 'bg-amber-50 text-amber-700', 1 => 'bg-emerald-50 text-emerald-700', 2 => 'bg-rose-50 text-rose-700'];
@endphp
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                <th class="px-4 py-3 font-semibold">Məhsul</th>
                <th class="px-4 py-3 font-semibold">Müştəri</th>
                <th class="px-4 py-3 font-semibold">Reytinq</th>
                <th class="px-4 py-3 font-semibold">Şərh</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold">Ana səhifə</th>
                <th class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($rows as $review)
                @php $status = $review->status; @endphp
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.products.show', $review->product_id) }}" class="font-medium text-gray-700 hover:text-brand-600">
                            {{ $review->product?->title ?? '—' }}
                        </a>
                    </td>
                    <td class="px-4 py-3">{{ $review->user?->name }} {{ $review->user?->surname }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex">
                            @for ($i = 1; $i <= 5; $i++)
                                @include('admin.partials.icon', ['name' => $i <= (int) $review->rate ? 'star-solid' : 'star', 'class' => 'h-4 w-4 '.($i <= (int) $review->rate ? 'text-amber-500' : 'text-gray-300')])
                            @endfor
                        </span>
                    </td>
                    <td class="max-w-72 px-4 py-3 text-gray-600">{{ \Illuminate\Support\Str::limit($review->comment, 80) }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $tones[$status->value] ?? 'bg-gray-100 text-gray-600' }}">{{ $status->label() }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.reviews.featured', $review->id) }}">
                            @csrf @method('PUT')
                            <button class="rounded-full px-2.5 py-1 text-xs font-medium {{ $review->is_featured ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                <span class="inline-flex items-center gap-1">
                                    @include('admin.partials.icon', ['name' => $review->is_featured ? 'star-solid' : 'star', 'class' => 'h-3.5 w-3.5'])
                                    {{ $review->is_featured ? 'Seçilmiş' : 'Seç' }}
                                </span>
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-1">
                            @if ($status->value !== 1)
                                <form method="POST" action="{{ route('admin.reviews.status', $review->id) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="status" value="1">
                                    <button class="rounded-lg p-2 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600" title="Təsdiqlə">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    </button>
                                </form>
                            @endif
                            @if ($status->value !== 2)
                                <form method="POST" action="{{ route('admin.reviews.status', $review->id) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="status" value="2">
                                    <button class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600" title="Rədd et">@include('admin.partials.icon', ['name' => 'x', 'class' => 'h-4 w-4'])</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.reviews.destroy', $review->id) }}">
                                @csrf @method('DELETE')
                                <button class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600" title="Sil" onclick="return confirm('Silinsin?')">@include('admin.partials.icon', ['name' => 'trash', 'class' => 'h-4 w-4'])</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Şərh yoxdur</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('admin.partials.pagination', ['rows' => $rows])
