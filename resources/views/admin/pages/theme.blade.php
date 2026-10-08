@extends('admin.layouts.app')

@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-700">
            <p class="font-semibold text-gray-800 dark:text-white">Tema rəngləri</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Vitrinin rəng palitrasını buradan idarə edin.</p>
        </div>

        <form method="POST" action="{{ route('admin.theme.update') }}" class="p-5">
            @csrf @method('PUT')
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
                <button class="rounded-lg bg-brand-600 px-6 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
            </div>
        </form>
    </div>
@endsection
