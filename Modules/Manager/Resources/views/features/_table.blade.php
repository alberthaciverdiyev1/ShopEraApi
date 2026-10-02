<div class="relative overflow-x-auto">
    <table class="w-full text-left text-sm text-gray-500">
        <thead class="bg-gray-50 text-xs uppercase text-gray-700">
            <tr><th class="px-4 py-3 font-semibold">Key</th><th class="px-4 py-3 font-semibold">Ad</th><th class="px-4 py-3 font-semibold">Tip</th><th class="px-4 py-3 font-semibold">Default</th><th class="px-4 py-3 font-semibold">Qrup</th><th class="px-4 py-3 text-right font-semibold">Əməliyyat</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $feature)
                <tr class="border-b border-gray-100 bg-white hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $feature->key }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $feature->name }}</td>
                    <td class="px-4 py-3">{{ $feature->type->value }}</td>
                    <td class="px-4 py-3">{{ $feature->default_value ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $feature->group ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-1">
                            <button hx-post="{{ route('manager.features.duplicate', $feature) }}" hx-target="#feature-table" hx-swap="innerHTML" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-600" title="Kopyala">⧉</button>
                            <button hx-get="{{ route('manager.features.edit', $feature) }}" hx-target="#modal-root" hx-swap="innerHTML" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-600">✎</button>
                            <button hx-delete="{{ route('manager.features.destroy', $feature) }}" hx-target="#feature-table" hx-swap="innerHTML" hx-confirm="Silinsin?" class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600">🗑</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Feature yoxdur</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
