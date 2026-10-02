@php $isEdit = (bool) $feature; $input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-brand-500 focus:ring-brand-500'; @endphp
<div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4"
     onclick="if (event.target === this) document.getElementById('modal-root').innerHTML=''">
    <div class="w-full max-w-lg rounded-xl bg-white shadow">
        <div class="flex items-center justify-between border-b border-gray-200 p-4">
            <h3 class="font-semibold text-gray-800">{{ $isEdit ? 'Feature redaktə' : 'Yeni feature' }}</h3>
            <button type="button" onclick="document.getElementById('modal-root').innerHTML=''" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">✕</button>
        </div>
        <form hx-post="{{ $isEdit ? route('manager.features.update', $feature) : route('manager.features.store') }}"
              hx-target="#feature-table" hx-swap="innerHTML" class="grid grid-cols-2 gap-4 p-6">
            @if ($isEdit)<input type="hidden" name="_method" value="PUT">@endif
            <div class="col-span-1"><label class="mb-1 block text-sm font-medium text-gray-700">Key</label><input name="key" value="{{ old('key', $feature?->key) }}" class="{{ $input }}"></div>
            <div class="col-span-1"><label class="mb-1 block text-sm font-medium text-gray-700">Ad</label><input name="name" value="{{ old('name', $feature?->name) }}" class="{{ $input }}"></div>
            <div class="col-span-1">
                <label class="mb-1 block text-sm font-medium text-gray-700">Tip</label>
                <select name="type" class="{{ $input }}">
                    <option value="bool" @selected(old('type', $feature?->type?->value) === 'bool')>bool</option>
                    <option value="limit" @selected(old('type', $feature?->type?->value) === 'limit')>number (limit)</option>
                </select>
            </div>
            <div class="col-span-1"><label class="mb-1 block text-sm font-medium text-gray-700">Default</label><input name="default_value" value="{{ old('default_value', $feature?->default_value) }}" class="{{ $input }}"></div>
            <div class="col-span-1"><label class="mb-1 block text-sm font-medium text-gray-700">Qrup</label><input name="group" value="{{ old('group', $feature?->group) }}" class="{{ $input }}"></div>
            <div class="col-span-1"><label class="mb-1 block text-sm font-medium text-gray-700">Sıra</label><input type="number" name="sort_order" value="{{ old('sort_order', $feature?->sort_order ?? 0) }}" class="{{ $input }}"></div>
            <div class="col-span-2"><label class="mb-1 block text-sm font-medium text-gray-700">Təsvir</label><textarea name="description" rows="2" class="{{ $input }}">{{ old('description', $feature?->description) }}</textarea></div>
            @if ($errors->any())<div class="col-span-2 text-sm text-rose-600">{{ $errors->first() }}</div>@endif
            <div class="col-span-2 flex justify-end gap-2 border-t border-gray-200 pt-4">
                <button type="button" onclick="document.getElementById('modal-root').innerHTML=''" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Ləğv et</button>
                <button class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
            </div>
        </form>
    </div>
</div>
