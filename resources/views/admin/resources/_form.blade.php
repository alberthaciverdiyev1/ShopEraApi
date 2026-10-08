@php
    $isEdit = ! is_null($item);
    $action = $isEdit ? route($route.'.update', $item->id) : route($route.'.store');
    $close = "document.getElementById('modal-root').innerHTML=''";
    $input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';

    $fieldValue = function (array $field, ?string $locale = null) use ($item) {
        $name = $field['name'];
        $type = $field['type'] ?? 'text';

        if ($locale !== null) {
            $values = $item && method_exists($item, 'getTranslations') ? $item->getTranslations($name) : [];
            return old("$name.$locale", $values[$locale] ?? '');
        }
        if ($type === 'multiselect' && ! empty($field['sync'])) {
            return old($name, $item ? $item->{$field['sync']}()->pluck('id')->all() : []);
        }
        if ($type === 'checkbox') {
            return $item ? (bool) $item->{$name} : (bool) ($field['default'] ?? true);
        }
        if ($type === 'lines') {
            $value = $item?->{$name};
            return old($name, is_array($value) ? implode("\n", $value) : (string) $value);
        }
        return old($name, $item?->{$name});
    };
@endphp

<div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4"
     onclick="if (event.target === this) {{ $close }}">
    <div class="relative w-full max-w-4xl">
        <div class="relative max-h-[90vh] overflow-y-auto rounded-xl bg-white shadow dark:bg-gray-800">
            <div class="flex items-center justify-between rounded-t border-b border-gray-200 p-4 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $isEdit ? 'Redaktə et' : 'Yeni əlavə et' }} — {{ $title }}
                </h3>
                <button type="button" onclick="{{ $close }}"
                        class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    @include('admin.partials.icon', ['name' => 'x', 'class' => 'h-5 w-5'])
                </button>
            </div>

            <form hx-post="{{ $action }}" hx-target="#resource-table" hx-swap="innerHTML"
                  hx-encoding="multipart/form-data" enctype="multipart/form-data"
                  class="grid grid-cols-12 gap-4 p-6">
                @if ($isEdit)
                    <input type="hidden" name="_method" value="PUT">
                @endif

                @foreach ($fields as $field)
                    @php
                        $name = $field['name'];
                        $type = $field['type'] ?? 'text';
                        $label = $field['label'] ?? $name;
                        $span = $field['col'] ?? 12;
                        $showWhen = $field['showWhen'] ?? null;
                        $hidden = false;
                        if ($showWhen) {
                            $controller = collect($fields)->firstWhere('name', $showWhen['field']);
                            $value = $controller ? $fieldValue($controller) : null;
                            $hidden = (string) $value !== (string) $showWhen['value'];
                        }
                    @endphp

                    <div style="grid-column: span {{ $span }} / span {{ $span }}" class="min-w-0"
                         @if ($showWhen) data-show-field="{{ $showWhen['field'] }}" data-show-value="{{ $showWhen['value'] }}" @endif
                         @class(['hidden' => $hidden])>
                        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</label>

                        @if (str_starts_with($type, 'translatable_'))
                            @php $inner = str_replace('translatable_', '', $type); @endphp
                            <div data-lang-tabs>
                                <div class="mb-3 flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-700">
                                    @foreach ($locales as $i => $locale)
                                        <button type="button" data-lang-tab="{{ $name }}:{{ $locale }}"
                                                class="flex-1 rounded-md px-3 py-1.5 text-xs font-semibold uppercase transition {{ $i === 0 ? 'bg-white text-brand-600 shadow-sm dark:bg-gray-800' : 'text-gray-500 hover:text-gray-700 dark:text-gray-300' }}">
                                            {{ $locale }}
                                        </button>
                                    @endforeach
                                </div>
                                @foreach ($locales as $i => $locale)
                                    @php $val = $fieldValue($field, $locale); @endphp
                                    <div data-lang-panel="{{ $name }}:{{ $locale }}" @class(['hidden' => $i > 0])>
                                        @if ($inner === 'textarea' || $inner === 'html')
                                            <textarea name="{{ $name }}[{{ $locale }}]" rows="4" class="{{ $input }}">{{ $val }}</textarea>
                                        @else
                                            <input type="text" name="{{ $name }}[{{ $locale }}]" value="{{ $val }}" class="{{ $input }}">
                                        @endif
                                        @error("$name.$locale")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                    </div>
                                @endforeach
                            </div>

                        @elseif ($type === 'textarea' || $type === 'lines')
                            <textarea name="{{ $name }}" rows="4" @if($type === 'lines') placeholder="Hər sətirdə bir seçim" @endif class="{{ $input }}">{{ $fieldValue($field) }}</textarea>

                        @elseif ($type === 'checkbox')
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="hidden" name="{{ $name }}" value="0">
                                <input type="checkbox" name="{{ $name }}" value="1" @checked($fieldValue($field)) class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-700"></div>
                            </label>

                        @elseif ($type === 'select')
                            @php $selected = (string) $fieldValue($field); @endphp
                            <select name="{{ $name }}" class="{{ $input }}">
                                @foreach (($options[$name] ?? []) as $optValue => $optLabel)
                                    <option value="{{ $optValue }}" @selected((string) $optValue === $selected)>{{ $optLabel }}</option>
                                @endforeach
                            </select>

                        @elseif ($type === 'multiselect')
                            @php $selected = array_map('strval', (array) $fieldValue($field)); @endphp
                            <select name="{{ $name }}[]" multiple size="6" class="{{ $input }}">
                                @foreach (($options[$name] ?? []) as $optValue => $optLabel)
                                    <option value="{{ $optValue }}" @selected(in_array((string) $optValue, $selected, true))>{{ $optLabel }}</option>
                                @endforeach
                            </select>

                        @elseif ($type === 'color')
                            <div class="flex items-center gap-3">
                                <input type="color" name="{{ $name }}" value="{{ $fieldValue($field) ?: '#000000' }}"
                                       class="h-11 w-14 cursor-pointer rounded-lg border border-gray-300 bg-gray-50 dark:border-gray-600">
                                <span class="text-sm text-gray-500">{{ $fieldValue($field) }}</span>
                            </div>

                        @elseif (in_array($type, ['file', 'image', 'video']))
                            @php
                                $current = $item?->{$name};
                                $isVideo = $type === 'video';
                                $accept = $type === 'image' ? 'image/*' : ($isVideo ? 'video/mp4,video/webm,video/quicktime,video/*' : '*/*');
                            @endphp
                            <div class="space-y-3">
                                @if ($current)
                                    <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-3 dark:border-gray-600 dark:bg-gray-700/40">
                                        @if ($isVideo)
                                            <video src="{{ $current }}" class="h-16 w-24 rounded-lg bg-gray-900 object-cover" muted controls></video>
                                        @else
                                            <img src="{{ $current }}" class="h-16 w-16 rounded-lg object-cover ring-1 ring-gray-200" alt="">
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Mövcud fayl</p>
                                            <label class="mt-1 inline-flex cursor-pointer items-center text-sm text-rose-600">
                                                <input type="checkbox" name="remove_{{ $name }}" value="1"
                                                       class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-rose-600 focus:ring-rose-500 dark:border-gray-600 dark:bg-gray-700">
                                                <span class="ms-2">Faylı sil</span>
                                            </label>
                                        </div>
                                    </div>
                                @endif
                                @include('admin.partials.media-upload', [
                                    'name' => $name,
                                    'accept' => $accept,
                                    'label' => $current ? 'Yenisini yüklə' : 'Fayl seçin',
                                    'hint' => $isVideo ? 'MP4 / WebM' : ($type === 'image' ? 'JPG, PNG, WebP' : 'İstənilən fayl'),
                                ])
                            </div>

                        @elseif ($type === 'date')
                            @php $dateVal = $fieldValue($field); $dateVal = $dateVal ? \Illuminate\Support\Carbon::parse($dateVal)->format('Y-m-d') : ''; @endphp
                            <input type="date" name="{{ $name }}" value="{{ $dateVal }}" class="{{ $input }}">

                        @elseif ($type === 'password')
                            @include('admin.partials.password-input', [
                                'name' => $name,
                                'autocomplete' => 'new-password',
                                'class' => $input,
                            ])

                        @else
                            <input type="{{ $type === 'number' ? 'number' : 'text' }}"
                                   @if (($field['step'] ?? false)) step="{{ $field['step'] }}" @endif
                                   @if (($field['readonly'] ?? false)) readonly @endif
                                   name="{{ $name }}" value="{{ $fieldValue($field) }}" class="{{ $input }}">
                        @endif

                        @error($name)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                <div class="col-span-12 flex items-center justify-end gap-2 rounded-b border-t border-gray-200 pt-4 dark:border-gray-700">
                    <button type="button" onclick="{{ $close }}"
                            class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        Ləğv et
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100">
                        {{ $isEdit ? 'Yadda saxla' : 'Əlavə et' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
