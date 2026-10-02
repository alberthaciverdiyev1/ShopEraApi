<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                <th class="px-4 py-3 font-semibold">Üzv</th>
                <th class="px-4 py-3 font-semibold">Əlaqə</th>
                <th class="px-4 py-3 font-semibold">Rollar</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($rows as $user)
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                            <span class="font-medium text-gray-700">{{ trim($user->name.' '.$user->surname) ?: '—' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-gray-700">{{ $user->phone }}</p>
                        <p class="text-xs text-gray-400">{{ $user->email }}</p>
                    </td>
                    <td class="px-4 py-3">
                        @foreach ($user->roles as $role)
                            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td class="px-4 py-3">
                        @if ($user->is_active)
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Aktiv</span>
                        @else
                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Bloklu</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.users.show', $user->id) }}" class="inline-block rounded-lg p-2 text-gray-500 hover:bg-brand-50 hover:text-brand-600" title="Bax">@include('admin.partials.icon', ['name' => 'eye', 'class' => 'h-4 w-4'])</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">Komanda üzvü yoxdur</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('admin.partials.pagination', ['rows' => $rows])
