@extends('admin.layouts.app')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="pb-20">
        @csrf @method('PUT')

        <div class="space-y-5">
            @foreach ($groups as $groupTitle => $group)
                <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <header class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/60 px-5 py-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $group['icon'] }}"/>
                            </svg>
                        </span>
                        <h2 class="font-semibold text-gray-800">{{ $groupTitle }}</h2>
                    </header>

                    <div class="grid grid-cols-12 gap-4 p-5">
                        @foreach ($group['fields'] as [$name, $label, $type, $col])
                            @php $value = old($name, $setting->{$name}); @endphp
                            <div style="grid-column: span {{ $col }} / span {{ $col }}" class="min-w-0">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</label>

                                @if ($type === 'image')
                                    <input type="file" name="{{ $name }}" accept="image/*"
                                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    @if ($value)
                                        <div class="mt-2 flex items-center gap-3">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($value) }}" alt="" class="h-10 rounded ring-1 ring-gray-200">
                                            <label class="flex items-center gap-1 text-xs text-gray-500">
                                                <input type="checkbox" name="remove_{{ $name }}" value="1"> Sil
                                            </label>
                                        </div>
                                    @endif
                                @elseif ($type === 'translatable_textarea')
                                    <div data-lang-tabs>
                                        <div class="mb-3 flex gap-1 rounded-lg bg-gray-100 p-1">
                                            @foreach ($locales as $i => $locale)
                                                <button type="button" data-lang-tab="{{ $name }}:{{ $locale }}"
                                                        class="flex-1 rounded-md px-3 py-1.5 text-xs font-semibold uppercase {{ $i === 0 ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">{{ $locale }}</button>
                                            @endforeach
                                        </div>
                                        @foreach ($locales as $i => $locale)
                                            <div data-lang-panel="{{ $name }}:{{ $locale }}" @class(['hidden' => $i > 0])>
                                                <textarea name="{{ $name }}[{{ $locale }}]" rows="3"
                                                          class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-brand-500">{{ old("$name.$locale", is_array($value) ? ($value[$locale] ?? '') : '') }}</textarea>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif ($type === 'textarea')
                                    <textarea name="{{ $name }}" rows="3"
                                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-brand-500">{{ $value }}</textarea>
                                @else
                                    <input type="{{ $type === 'number' ? 'number' : 'text' }}" step="any" name="{{ $name }}" value="{{ $value }}"
                                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-brand-500">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="fixed inset-x-0 bottom-0 z-10 border-t border-gray-200 bg-white/90 px-5 py-3 backdrop-blur lg:pl-64">
            <div class="mx-auto flex max-w-6xl justify-end">
                <button class="rounded-lg bg-brand-600 px-8 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    Yadda saxla
                </button>
            </div>
        </div>
    </form>
@endsection
