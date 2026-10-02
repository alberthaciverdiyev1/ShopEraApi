@extends('manager::layouts.app')
@section('content')
    @php $isEdit = (bool) $block; $input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-brand-500 focus:ring-brand-500'; $label = 'mb-1 block text-sm font-medium text-gray-700'; @endphp
    <form method="POST" action="{{ $isEdit ? route('manager.promo-blocks.update', $block) : route('manager.promo-blocks.store') }}"
          enctype="multipart/form-data" class="mx-auto max-w-3xl space-y-5 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf @if ($isEdit) @method('PUT') @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="{{ $label }}">Tip</label>
                <select name="type" class="{{ $input }}">
                    <option value="offer" @selected(old('type', $block?->type) === 'offer')>Limited-time / Bundle offer</option>
                    <option value="ad" @selected(old('type', $block?->type) === 'ad')>Reklam (ad)</option>
                </select>
            </div>
            <div><label class="{{ $label }}">Badge (məs. -30%)</label><input name="badge" value="{{ old('badge', $block?->badge) }}" class="{{ $input }}"></div>
            <div><label class="{{ $label }}">Başlıq</label><input name="title" value="{{ old('title', $block?->title) }}" class="{{ $input }}"></div>
            <div><label class="{{ $label }}">Alt başlıq</label><input name="subtitle" value="{{ old('subtitle', $block?->subtitle) }}" class="{{ $input }}"></div>
            <div class="col-span-2"><label class="{{ $label }}">Təsvir</label><textarea name="description" rows="2" class="{{ $input }}">{{ old('description', $block?->description) }}</textarea></div>
            <div><label class="{{ $label }}">Düymə mətni</label><input name="button_text" value="{{ old('button_text', $block?->button_text) }}" class="{{ $input }}" placeholder="Məs. İndi al"></div>
            <div><label class="{{ $label }}">Link (URL)</label><input name="url" value="{{ old('url', $block?->url) }}" class="{{ $input }}" placeholder="/shop"></div>
            <div><label class="{{ $label }}">Sıra</label><input type="number" name="sort_order" value="{{ old('sort_order', $block?->sort_order ?? 0) }}" class="{{ $input }}"></div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $block?->is_active ?? true)) class="h-4 w-4 rounded border-gray-300 text-brand-600">Aktiv
                </label>
            </div>
            <div class="col-span-2">
                <label class="{{ $label }}">Şəkil</label>
                <input type="file" name="image" accept="image/*" class="block w-full text-sm text-gray-500">
                @if ($block?->getRawOriginal('image'))<img src="{{ $block->image }}" class="mt-2 h-20 rounded-lg object-cover ring-1 ring-gray-200">@endif
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
            <a href="{{ route('manager.promo-blocks.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Ləğv et</a>
            <button class="rounded-lg bg-brand-600 px-6 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
        </div>
    </form>
@endsection
