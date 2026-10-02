@php
    $previewId = $previewId ?? 'preview-'.str_replace(['[', ']', '_'], ['-', '', '-'], $name);
    $accept = $accept ?? '*/*';
@endphp
<div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/70 p-4 transition hover:border-brand-400 hover:bg-brand-50/40 dark:border-gray-600 dark:bg-gray-700/30 dark:hover:border-brand-500">
    <label class="flex cursor-pointer flex-col items-center justify-center gap-1 text-center">
        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm ring-1 ring-gray-200 dark:bg-gray-700 dark:ring-gray-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
        </span>
        <span class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label ?? 'Fayl seçin' }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $hint ?? '' }}</span>
        <input type="file" name="{{ $name }}" @if (! empty($multiple)) multiple @endif accept="{{ $accept }}"
               data-preview="{{ $previewId }}" class="sr-only">
    </label>
    <div id="{{ $previewId }}" class="mt-3 flex flex-wrap justify-center gap-2 empty:hidden"></div>
</div>
