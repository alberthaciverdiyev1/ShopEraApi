@extends('admin.layouts.app')

@php
    $input = 'w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white';
    $labelCls = 'mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300';
    // Indented options for a filter's values (depth 3 is plenty here).
    $valueOptions = function ($filter) {
        $out = [];
        $walk = function ($parentId, $depth) use (&$walk, &$out, $filter) {
            foreach ($filter->values->where('parent_value_id', $parentId) as $v) {
                $out[$v->id] = str_repeat('— ', $depth).admin_label($v, 'title', '#'.$v->id);
                $walk($v->id, $depth + 1);
            }
        };
        $walk(null, 0);
        return $out;
    };
    $catLabel = fn ($c) => trim(str_repeat('— ', $c->parent_id ? 1 : 0).admin_label($c, 'name', '#'.$c->id));
@endphp

@section('content')
    <div class="mx-auto max-w-4xl space-y-5">
        <form method="GET" action="{{ route('admin.listing-filters.index') }}"
              class="flex items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex-1">
                <label class="{{ $labelCls }}">Kateqoriya</label>
                <select name="category_id" onchange="this.form.submit()" class="{{ $input }}">
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($category->id === $categoryId)>{{ $catLabel($category) }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        {{-- Add filter --}}
        <form method="POST" action="{{ route('admin.listing-filters.storeFilter') }}"
              class="rounded-lg border border-dashed border-gray-300 bg-white p-4 dark:border-gray-600 dark:bg-gray-800">
            @csrf
            <input type="hidden" name="category_id" value="{{ $categoryId }}">
            <p class="mb-3 font-semibold text-gray-800 dark:text-white">Yeni filtr</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="{{ $labelCls }}">Ad</label>
                    <input name="title" required placeholder="Məsələn: Marka" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Asılı olduğu filtr</label>
                    <select name="depends_on_filter_id" class="{{ $input }}">
                        <option value="">— yoxdur —</option>
                        @foreach ($filters as $f)<option value="{{ $f->id }}">{{ admin_label($f, 'title') }}</option>@endforeach
                    </select>
                </div>
                <div class="flex items-end gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="checkbox" name="required" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-600"> Məcburi
                    </label>
                    <input name="sort_order" type="number" min="0" value="0" class="{{ $input }} w-20" title="Sıra">
                </div>
            </div>
            <button class="mt-3 rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">Əlavə et</button>
        </form>

        {{-- Existing filters --}}
        @forelse ($filters as $filter)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <form method="POST" action="{{ route('admin.listing-filters.updateFilter', $filter->id) }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    @csrf @method('PUT')
                    <div class="sm:col-span-2">
                        <label class="{{ $labelCls }}">Ad</label>
                        <input name="title" value="{{ admin_label($filter, 'title') }}" required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $labelCls }}">Asılı olduğu filtr</label>
                        <select name="depends_on_filter_id" class="{{ $input }}">
                            <option value="">— yoxdur —</option>
                            @foreach ($filters as $other)
                                @if ($other->id !== $filter->id)
                                    <option value="{{ $other->id }}" @selected($filter->depends_on_filter_id === $other->id)>{{ admin_label($other, 'title') }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input type="checkbox" name="required" value="1" @checked($filter->required) class="h-4 w-4 rounded border-gray-300 text-brand-600"> Məcburi
                        </label>
                        <input name="sort_order" type="number" min="0" value="{{ $filter->sort_order }}" class="{{ $input }} w-20" title="Sıra">
                        <button class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900">Yadda saxla</button>
                    </div>
                </form>

                {{-- Values --}}
                <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-700">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Dəyərlər</p>
                    <div class="flex flex-col gap-1">
                        @php $options = $valueOptions($filter); @endphp
                        @forelse ($options as $id => $label)
                            <div class="flex items-center justify-between rounded border border-gray-100 px-3 py-1.5 text-sm dark:border-gray-700">
                                <span class="text-gray-700 dark:text-gray-200">{{ $label }}</span>
                                <form method="POST" action="{{ route('admin.listing-filters.destroyValue', $id) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-rose-600 hover:underline">Sil</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">Dəyər yoxdur.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('admin.listing-filters.storeValue') }}" class="mt-3 flex flex-wrap items-end gap-2">
                        @csrf
                        <input type="hidden" name="filter_id" value="{{ $filter->id }}">
                        <div class="min-w-40 flex-1">
                            <input name="title" required placeholder="Yeni dəyər (məs. Apple)" class="{{ $input }}">
                        </div>
                        <div class="min-w-40 flex-1">
                            <select name="parent_value_id" class="{{ $input }}">
                                <option value="">— ata dəyər yoxdur (kök) —</option>
                                @foreach ($options as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Dəyər əlavə et</button>
                    </form>
                </div>

                <form method="POST" action="{{ route('admin.listing-filters.destroyFilter', $filter->id) }}" class="mt-3 flex justify-end border-t border-gray-100 pt-3 dark:border-gray-700">
                    @csrf @method('DELETE')
                    <button class="text-xs text-rose-600 hover:underline">Filtri sil</button>
                </form>
            </div>
        @empty
            <p class="rounded-lg border border-gray-200 bg-white px-4 py-10 text-center text-sm text-gray-400 dark:border-gray-700 dark:bg-gray-800">Bu kateqoriyada filtr yoxdur.</p>
        @endforelse
    </div>
@endsection
