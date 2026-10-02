@php
    $translatable = function ($row, string $key) {
        $map = method_exists($row, 'getTranslations') ? $row->getTranslations($key) : null;
        if (! is_array($map) || $map === []) {
            $value = $row->{$key};
            $map = is_array($value) ? $value : ['az' => $value];
        }
        return $map['az'] ?? reset($map) ?? '';
    };
@endphp

<div class="relative overflow-x-auto">
    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                @foreach ($columns as $column)
                    <th scope="col" class="px-4 py-3 font-semibold">{{ $column['label'] ?? '' }}</th>
                @endforeach
                <th scope="col" class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="border-b border-gray-100 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-600">
                    @foreach ($columns as $column)
                        @php $key = $column['key']; $type = $column['type'] ?? 'text'; $value = $row->{$key}; @endphp
                        <td class="px-4 py-3 align-middle">
                            @switch($type)
                                @case('image')
                                    @if ($value)
                                        <img src="{{ $value }}" alt="" class="h-10 w-10 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-gray-600">
                                    @else
                                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-gray-700">—</span>
                                    @endif
                                    @break
                                @case('color')
                                    <span class="inline-flex items-center gap-2">
                                        <span class="h-6 w-6 rounded-full ring-1 ring-gray-200 dark:ring-gray-600" style="background: {{ $value ?: '#e5e7eb' }}"></span>
                                        <span class="text-xs text-gray-400">{{ $value }}</span>
                                    </span>
                                    @break
                                @case('boolean')
                                    @if ($value)
                                        <span class="rounded bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">Aktiv</span>
                                    @else
                                        <span class="rounded bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Deaktiv</span>
                                    @endif
                                    @break
                                @case('translatable')
                                    {{ \Illuminate\Support\Str::limit($translatable($row, $key), 60) }}
                                    @break
                                @case('number')
                                    {{ $value === null || $value === '' ? '—' : $value }}
                                    @break
                                @case('date')
                                    {{ $value ? \Illuminate\Support\Carbon::parse($value)->format('d.m.Y') : '—' }}
                                    @break
                                @case('datetime')
                                    {{ $value ? \Illuminate\Support\Carbon::parse($value)->format('d.m.Y H:i') : '—' }}
                                    @break
                                @case('money')
                                    {{ $value === null || $value === '' ? '—' : number_format((float) $value, 2) }} ₼
                                    @break
                                @case('list')
                                    {{ is_array($value) ? implode(', ', $value) : $value }}
                                    @break
                                @case('map')
                                    {{ $columnMaps[$key][$value] ?? ($value ?? '—') }}
                                    @break
                                @default
                                    {{ \Illuminate\Support\Str::limit(strip_tags((string) $value), 60) ?: '—' }}
                            @endswitch
                        </td>
                    @endforeach
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            @if (Route::has($route.'.edit'))
                                <button type="button"
                                        hx-get="{{ route($route.'.edit', $row->id) }}" hx-target="#modal-root" hx-swap="innerHTML"
                                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-gray-600" title="Redaktə">
                                    @include('admin.partials.icon', ['name' => 'pencil'])
                                </button>
                            @endif
                            @if (Route::has($route.'.destroy'))
                                <button type="button"
                                        hx-delete="{{ route($route.'.destroy', $row->id) }}"
                                        hx-target="#resource-table" hx-swap="innerHTML"
                                        hx-confirm="Silinsin?"
                                        class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600 dark:text-gray-400 dark:hover:bg-gray-600" title="Sil">
                                    @include('admin.partials.icon', ['name' => 'trash'])
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="px-4 py-12 text-center text-gray-400">Məlumat yoxdur</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.partials.pagination', ['rows' => $rows])
