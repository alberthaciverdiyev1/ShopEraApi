@extends('manager::layouts.app')
@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-200 p-4">
            <p class="font-semibold text-gray-700">Tema presetləri</p>
            <a href="{{ route('manager.themes.create') }}" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Yeni tema</a>
        </div>
        <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($themes as $theme)
                @php $accent = $theme->colors->firstWhere('key', '--theme')?->value ?? '#4f46e5'; @endphp
                <div class="rounded-lg border {{ $theme->is_default ? 'border-brand-500' : 'border-gray-200' }} bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="h-10 w-10 rounded-lg ring-1 ring-gray-200" style="background: {{ $accent }}"></span>
                            <div>
                                <p class="font-semibold text-gray-800">{{ $theme->name }}</p>
                                <p class="font-mono text-xs text-gray-400">{{ $theme->slug }}</p>
                            </div>
                        </div>
                        @if ($theme->is_default)
                            <span class="rounded bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">Default</span>
                        @endif
                    </div>
                    <p class="mt-3 min-h-8 text-xs text-gray-500">{{ $theme->description }}</p>
                    <div class="mt-2 flex gap-2">
                        @foreach ($theme->colors->sortBy('id')->take(6) as $color)
                            <span class="h-5 w-5 rounded-full ring-1 ring-gray-200" style="background: {{ $color->value }}" title="{{ $color->key }}"></span>
                        @endforeach
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3">
                        <span class="text-xs {{ $theme->is_active ? 'text-emerald-600' : 'text-gray-400' }}">{{ $theme->is_active ? 'Aktiv' : 'Deaktiv' }}</span>
                        <div class="flex gap-1">
                            <a href="{{ route('manager.themes.edit', $theme) }}" class="rounded-lg px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50">Palitra</a>
                            @unless ($theme->is_default)
                                <form method="POST" action="{{ route('manager.themes.default', $theme) }}">@csrf<button class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100">Default et</button></form>
                                <form method="POST" action="{{ route('manager.themes.destroy', $theme) }}">@csrf @method('DELETE')<button class="rounded-lg px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50" onclick="return confirm('Silinsin?')">Sil</button></form>
                            @endunless
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
