<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                <th class="px-4 py-3 font-semibold">Başlıq</th>
                <th class="px-4 py-3 font-semibold">Mətn</th>
                <th class="px-4 py-3 font-semibold">Hədəf</th>
                <th class="px-4 py-3 font-semibold">Tarix</th>
                <th class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($rows as $item)
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3 font-medium text-gray-700">{{ $item->title }}</td>
                    <td class="max-w-80 px-4 py-3 text-gray-600">{{ \Illuminate\Support\Str::limit($item->body, 90) }}</td>
                    <td class="px-4 py-3">
                        @if ($item->all)
                            <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700">Hamı</span>
                        @else
                            <span class="text-xs text-gray-500">{{ $item->users->first()?->name ?? '—' }} ({{ $item->users->count() }})</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $item->created_at?->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('admin.notifications.destroy', $item->id) }}">
                            @csrf @method('DELETE')
                            <button class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600" onclick="return confirm('Silinsin?')">@include('admin.partials.icon', ['name' => 'trash', 'class' => 'h-4 w-4'])</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">Bildiriş yoxdur</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('admin.partials.pagination', ['rows' => $rows])
