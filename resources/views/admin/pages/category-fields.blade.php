@extends('admin.layouts.app')

@php
    $labels = [
        'title' => 'Başlıq',
        'description' => 'Təsvir',
        'price' => 'Qiymət',
        'photos' => 'Şəkillər',
        'city' => 'Şəhər',
        'condition' => 'Vəziyyət (Yeni)',
        'delivery' => 'Çatdırılma',
    ];
    $labelOf = fn ($c) => trim(str_repeat('— ', max(0, (int) ($c->parent_id ? 1 : 0))).admin_label($c, 'name', '#'.$c->id));
@endphp

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <form method="GET" action="{{ route('admin.category-fields.index') }}"
              class="flex items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Kateqoriya</label>
                <select name="category_id" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($category->id === $categoryId)>{{ $labelOf($category) }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.category-fields.update') }}"
              class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @csrf @method('PUT')
            <input type="hidden" name="category_id" value="{{ $categoryId }}">

            <p class="mb-1 font-semibold text-gray-800 dark:text-white">Elan formu sahələri</p>
            <p class="mb-4 text-xs text-gray-500">Seçilmiş kateqoriyada elan verərkən hansı sahələr görünsün və hansılar məcburi olsun.</p>

            <div class="overflow-hidden rounded-lg border border-gray-100 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:bg-gray-700/40">
                            <th class="px-4 py-2 font-semibold">Sahə</th>
                            <th class="px-4 py-2 text-center font-semibold">Görünsün</th>
                            <th class="px-4 py-2 text-center font-semibold">Məcburi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        @foreach ($fields as $field)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-700 dark:text-gray-200">{{ $labels[$field] ?? $field }}</td>
                                <td class="px-4 py-3 text-center">
                                    <input type="hidden" name="fields[{{ $field }}][visible]" value="0">
                                    <input type="checkbox" name="fields[{{ $field }}][visible]" value="1"
                                           @checked($schema[$field]['visible'] ?? true)
                                           class="h-4 w-4 rounded border-gray-300 text-brand-600">
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input type="hidden" name="fields[{{ $field }}][required]" value="0">
                                    <input type="checkbox" name="fields[{{ $field }}][required]" value="1"
                                           @checked($schema[$field]['required'] ?? false)
                                           class="h-4 w-4 rounded border-gray-300 text-brand-600">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                <button class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
            </div>
        </form>
    </div>
@endsection
