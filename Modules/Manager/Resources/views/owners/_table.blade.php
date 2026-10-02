<div class="relative overflow-x-auto">
    <table class="w-full text-left text-sm text-gray-500">
        <thead class="bg-gray-50 text-xs uppercase text-gray-700">
            <tr>
                <th class="px-4 py-3 font-semibold">Sahib</th>
                <th class="px-4 py-3 font-semibold">Domenlər</th>
                <th class="px-4 py-3 font-semibold">Plan</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($owners as $owner)
                <tr class="border-b border-gray-100 bg-white hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('manager.owners.edit', $owner) }}" class="font-medium text-gray-800 hover:text-brand-600">{{ $owner->name }}</a>
                        <p class="text-xs text-gray-400">{{ $owner->email }}</p>
                    </td>
                    <td class="px-4 py-3">
                        @forelse ($owner->domains as $domain)
                            <span class="inline-block rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $domain->host }}</span>
                        @empty <span class="text-gray-400">—</span> @endforelse
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $owner->currentSubscription?->plan?->name ?? '—' }}</td>
                    <td class="px-4 py-3"><span class="rounded bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">{{ $owner->status->label() }}</span></td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-1">
                            <form method="POST" action="{{ route('manager.owners.push', $owner) }}" class="inline">
                                @csrf
                                <button type="submit" title="Plan/feature dəyərlərini bazaya yaz" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-emerald-600">⟳</button>
                            </form>
                            <a href="{{ route('manager.owners.edit', $owner) }}" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-600">✎</a>
                            <form method="POST" action="{{ route('manager.owners.destroy', $owner) }}">
                                @csrf @method('DELETE')
                                <button class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600" onclick="return confirm('Silinsin?')">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">Sahib yoxdur</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="border-t border-gray-200 p-4">{{ $owners->links() }}</div>
