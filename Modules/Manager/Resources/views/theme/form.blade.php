@extends('manager::layouts.app')
@section('content')
    @php $isEdit = (bool) $theme; $input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-brand-500 focus:ring-brand-500'; @endphp
    <form method="POST" action="{{ $isEdit ? route('manager.themes.update', $theme) : route('manager.themes.store') }}" class="space-y-5 pb-16">
        @csrf @if ($isEdit) @method('PUT') @endif

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="mb-4 font-semibold text-gray-800">Tema</p>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Ad</label><input name="name" value="{{ old('name', $theme?->name) }}" required class="{{ $input }}"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Slug</label><input name="slug" value="{{ old('slug', $theme?->slug) }}" class="{{ $input }}" placeholder="avtomatik"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Sıra</label><input type="number" name="sort_order" value="{{ old('sort_order', $theme?->sort_order ?? 0) }}" class="{{ $input }}"></div>
                <div class="flex items-end gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $theme?->is_active ?? true)) class="h-4 w-4 rounded border-gray-300 text-brand-600">Aktiv
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $theme?->is_default ?? false)) class="h-4 w-4 rounded border-gray-300 text-brand-600">Default
                    </label>
                </div>
                <div class="col-span-2 sm:col-span-4"><label class="mb-1 block text-sm font-medium text-gray-700">Təsvir</label><input name="description" value="{{ old('description', $theme?->description) }}" class="{{ $input }}"></div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="mb-4 font-semibold text-gray-800">Palitra</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($labels as $key => $label)
                    @php $val = old("colors.$key", $colors[$key] ?? '#000000'); $isColor = str_starts_with(trim((string) $val), '#'); @endphp
                    <div class="flex items-center gap-3 rounded-xl border border-gray-100 p-3">
                        <span class="h-9 w-9 shrink-0 rounded-lg ring-1 ring-gray-200" style="background: {{ $val }}"></span>
                        <div class="min-w-0 flex-1">
                            <label class="block truncate text-xs font-semibold text-gray-600">{{ $label }} <span class="font-mono text-[10px] text-gray-400">{{ $key }}</span></label>
                            <input name="colors[{{ $key }}]" value="{{ $val }}" class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs font-mono focus:border-brand-500">
                        </div>
                        @if ($isColor)
                            <input type="color" value="{{ $val }}" oninput="this.previousElementSibling.querySelector('input').value = this.value" class="h-9 w-9 shrink-0 cursor-pointer rounded-lg border border-gray-200">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-10 border-t border-gray-200 bg-white/95 px-5 py-3 backdrop-blur">
            <div class="mx-auto flex max-w-6xl justify-end gap-2">
                <a href="{{ route('manager.themes.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Geri</a>
                <button class="rounded-lg bg-brand-600 px-6 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
            </div>
        </div>
    </form>
@endsection
