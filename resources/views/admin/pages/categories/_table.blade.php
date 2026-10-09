<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                <th class="px-4 py-3 font-semibold">Ad</th>
                <th class="px-4 py-3 font-semibold">Sıra</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($nodes as $node)
                @php $category = $node['category']; @endphp
                <tr data-chain="{{ $node['chain'] }}" @class(['hidden' => $node['depth'] > 0, 'hover:bg-gray-50/60' => true])>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2" style="padding-left: {{ $node['depth'] * 22 }}px">
                            @php $label = admin_label($category); @endphp
                            @if ($node['hasChildren'])
                                <button type="button" data-toggle-children="{{ $category->id }}"
                                        class="flex items-center gap-2 rounded-lg px-1.5 py-1 text-left transition hover:bg-gray-100"
                                        title="Alt kateqoriyaları göstər / gizlət">
                                    <svg class="tree-chevron h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                    @if ($category->image)
                                        <div class="relative">
                                            <img src="{{ $category->image }}" class="h-8 w-8 rounded-lg object-cover ring-1 ring-gray-200" style="@if($category->background_color) background-color: {{ $category->background_color }}; @endif">
                                            @if ($category->background_color)
                                                <span class="absolute -bottom-1 -right-1 h-3 w-3 rounded-full border border-white" style="background-color: {{ $category->background_color }}" title="Fon: {{ $category->background_color }}"></span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="relative">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-300" style="@if($category->background_color) background-color: {{ $category->background_color }}; @endif">—</span>
                                            @if ($category->background_color)
                                                <span class="absolute -bottom-1 -right-1 h-3 w-3 rounded-full border border-white" style="background-color: {{ $category->background_color }}" title="Fon: {{ $category->background_color }}"></span>
                                            @endif
                                        </div>
                                    @endif
                                    <span class="font-medium {{ $node['depth'] > 0 ? 'text-gray-600' : 'text-gray-800' }}">{{ $label }}</span>
                                </button>
                            @else
                                <span class="flex items-center gap-2 px-1.5 py-1">
                                    <span class="h-3.5 w-3.5 shrink-0"></span>
                                    @if ($category->image)
                                        <div class="relative">
                                            <img src="{{ $category->image }}" class="h-8 w-8 rounded-lg object-cover ring-1 ring-gray-200" style="@if($category->background_color) background-color: {{ $category->background_color }}; @endif">
                                            @if ($category->background_color)
                                                <span class="absolute -bottom-1 -right-1 h-3 w-3 rounded-full border border-white" style="background-color: {{ $category->background_color }}" title="Fon: {{ $category->background_color }}"></span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="relative">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-300" style="@if($category->background_color) background-color: {{ $category->background_color }}; @endif">—</span>
                                            @if ($category->background_color)
                                                <span class="absolute -bottom-1 -right-1 h-3 w-3 rounded-full border border-white" style="background-color: {{ $category->background_color }}" title="Fon: {{ $category->background_color }}"></span>
                                            @endif
                                        </div>
                                    @endif
                                    <span class="font-medium {{ $node['depth'] > 0 ? 'text-gray-600' : 'text-gray-800' }}">{{ $label }}</span>
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $category->sort_order }}</td>
                    <td class="px-4 py-3">
                        @if ($category->is_active)
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Aktiv</span>
                        @else
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">Deaktiv</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-1">
                            <button type="button"
                                    hx-get="{{ route($route.'.edit', $category->id) }}" hx-target="#modal-root" hx-swap="innerHTML"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-brand-50 hover:text-brand-600" title="Redaktə">
                                @include('admin.partials.icon', ['name' => 'pencil'])
                            </button>
                            <button type="button"
                                    hx-delete="{{ route($route.'.destroy', $category->id) }}"
                                    hx-target="#resource-table" hx-swap="innerHTML"
                                    hx-confirm="Silinsin?"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600" title="Sil">
                                @include('admin.partials.icon', ['name' => 'trash'])
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-12 text-center text-gray-400">Məlumat yoxdur</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
