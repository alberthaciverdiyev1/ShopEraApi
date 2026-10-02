@extends('admin.layouts.app')

@section('content')
    @if ($error)
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $error }}
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-700">
            <p class="font-semibold text-gray-800 dark:text-white">Mövcud temalar</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Temalar Manager.Snaker tərəfindən idarə olunur. Seçdiyin tema ana səhifədə tətbiq olunur.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($themes as $theme)
                @php
                    $accent = $theme['preview']['--theme'] ?? '#4f46e5';
                    $isCurrent = (int) ($theme['id'] ?? 0) === (int) $current;
                @endphp
                <div class="rounded-lg border {{ $isCurrent ? 'border-brand-500 ring-1 ring-brand-200' : 'border-gray-200' }} bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="h-10 w-10 rounded-lg ring-1 ring-gray-200" style="background: {{ $accent }}"></span>
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-white">{{ $theme['name'] }}</p>
                                <p class="text-xs text-gray-400">{{ $theme['slug'] }}</p>
                            </div>
                        </div>
                        @if ($isCurrent)
                            <span class="rounded bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">Aktiv</span>
                        @elseif (! empty($theme['is_default']))
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-500">Default</span>
                        @endif
                    </div>

                    <p class="mt-3 min-h-8 text-xs text-gray-500 dark:text-gray-400">{{ $theme['description'] }}</p>

                    <div class="mt-2 flex gap-2">
                        @foreach (array_slice($theme['preview'] ?? [], 0, 6) as $key => $value)
                            <span class="h-5 w-5 rounded-full ring-1 ring-gray-200" style="background: {{ $value }}" title="{{ $key }}"></span>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('admin.theme.select') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="theme_id" value="{{ $theme['id'] }}">
                        <button @if ($isCurrent) disabled @endif
                                class="w-full rounded-lg px-4 py-2 text-sm font-medium {{ $isCurrent ? 'cursor-default bg-gray-100 text-gray-400' : 'bg-brand-600 text-white hover:bg-brand-700' }}">
                            {{ $isCurrent ? 'Seçilib' : 'Bu temanı seç' }}
                        </button>
                    </form>
                </div>
            @empty
                <p class="col-span-full py-10 text-center text-sm text-gray-400">Tema tapılmadı.</p>
            @endforelse
        </div>
    </div>

    @if ($customThemeEnabled)
        <form method="POST" action="{{ route('admin.theme.update') }}" class="mt-5 rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @csrf @method('PUT')
            <p class="font-semibold text-gray-800 dark:text-white">Custom tema</p>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Abunəliyinə daxildir — öz rəng palitranı yarada bilərsən. Bu, seçilmiş Manager temasını üstələyir.</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($localColors as $color)
                    @php $isColor = str_starts_with(trim((string) $color->value), '#'); @endphp
                    <div class="flex items-center gap-3 rounded-xl border border-gray-100 p-3 dark:border-gray-700">
                        <span class="h-9 w-9 shrink-0 rounded-lg ring-1 ring-gray-200" style="background: {{ $color->value }}"></span>
                        <div class="min-w-0 flex-1">
                            <label class="block truncate text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $color->label ?? $color->key }} <span class="font-mono text-[10px] text-gray-400">{{ $color->key }}</span></label>
                            <input name="colors[{{ $color->key }}]" value="{{ $color->value }}" class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs font-mono focus:border-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        @if ($isColor)
                            <input type="color" value="{{ $color->value }}" oninput="this.previousElementSibling.querySelector('input').value = this.value" class="h-9 w-9 shrink-0 cursor-pointer rounded-lg border border-gray-200">
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                <button class="rounded-lg bg-brand-600 px-6 py-2 text-sm font-semibold text-white hover:bg-brand-700">Custom temanı yadda saxla</button>
            </div>
        </form>
    @endif

    @if (! empty($palette))
        <div class="mt-5 rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="mb-3 font-semibold text-gray-800 dark:text-white">Aktiv temanın palitrası</p>
            <div class="flex flex-wrap gap-3">
                @foreach ($palette as $key => $value)
                    <div class="flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-700">
                        <span class="h-6 w-6 rounded-full ring-1 ring-gray-200" style="background: {{ $value }}"></span>
                        <span class="font-mono text-xs text-gray-500">{{ $key }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
