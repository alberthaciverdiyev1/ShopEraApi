@extends('admin.layouts.app')

@php
    $waDigits = preg_replace('/\D+/', '', (string) $whatsapp);
    $waText = rawurlencode('Salam! Snaker admin panelində planı yüksəltmək istəyirəm.');
    $waHref = $waDigits ? "https://wa.me/{$waDigits}?text={$waText}" : null;
    $isIncluded = fn ($value) => ! in_array(strtolower((string) $value), ['', '0', 'false', 'no', 'off'], true);
@endphp

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        {{-- Current plan + limits/usage (moved here from the storefront) --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Cari plan</p>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $currentPlan ?: 'Free' }}</h2>
                </div>
                <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600 dark:bg-brand-500/15">{{ $currentPlan ?: 'Free' }}</span>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($limits as $row)
                    @php
                        $limit = $row['limit'];
                        $used = $row['used'];
                        $pct = ($limit !== null && $limit > 0) ? min(100, (int) round($used / $limit * 100)) : null;
                    @endphp
                    <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $row['label'] }}</span>
                            <span class="text-gray-500">{{ $used }} / {{ $limit === null ? '∞' : $limit }}</span>
                        </div>
                        @if ($pct !== null)
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-full rounded-full {{ $pct >= 90 ? 'bg-rose-500' : 'bg-brand-600' }}" style="width: {{ $pct }}%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-6 dark:border-amber-800 dark:from-gray-800 dark:to-gray-800">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                Premium
            </span>
            <h2 class="mt-3 text-xl font-bold text-gray-900 dark:text-white">
                Siz hazırda {{ $currentPlan ? $currentPlan : 'Free' }} plandasınız
            </h2>
            <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                Bu bölmə abunəliyinizə daxil deyil. Aşağıdakı planlara keçməklə bütün funksiyaları açın.
                Sifariş üçün bizə WhatsApp ilə yazın.
            </p>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                @if ($waHref)
                    <a href="{{ $waHref }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm5.8 14.13c-.24.68-1.42 1.3-1.96 1.38-.5.07-1.13.1-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.19-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.27-.29.59-.36.78-.36.2 0 .39 0 .56.01.18.01.42-.07.66.5.24.58.83 2.01.9 2.16.07.14.12.31.02.5-.1.19-.15.31-.29.48-.14.17-.3.37-.43.5-.14.14-.29.29-.12.57.17.29.75 1.24 1.61 2.01 1.11.99 2.04 1.29 2.33 1.44.29.14.46.12.63-.07.17-.19.73-.85.92-1.14.19-.29.39-.24.66-.14.27.1 1.7.8 1.99.95.29.14.48.22.55.34.07.12.07.7-.17 1.38Z"/></svg>
                        WhatsApp ilə əlaqə
                    </a>
                @endif
                @if ($supportEmail)
                    <a href="mailto:{{ $supportEmail }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        {{ $supportEmail }}
                    </a>
                @endif
            </div>
            @if (! $waHref)
                <p class="mt-3 text-xs text-gray-400">WhatsApp nömrəsi hələ təyin olunmayıb.</p>
            @endif
        </div>

        @if (count($plans))
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($plans as $plan)
                    @php $current = strcasecmp((string) $currentPlan, (string) $plan->name) === 0; @endphp
                    <div class="flex flex-col rounded-2xl border {{ $current ? 'border-brand-500 ring-1 ring-brand-500' : 'border-gray-200' }} bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                            <div class="text-right">
                                <span class="text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format((float) $plan->price, 0) }} ₼</span>
                                <span class="block text-xs text-gray-400">/ ay</span>
                            </div>
                        </div>
                        @if ($current)
                            <span class="mt-2 w-fit rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600">Cari plan</span>
                        @endif

                        <ul class="mt-4 space-y-2 text-sm">
                            @forelse ($plan->features as $feature)
                                @php $included = $isIncluded($feature->pivot->value); $isLimit = $feature->type?->value === 'limit'; @endphp
                                @if ($included || $isLimit)
                                    <li class="flex items-start gap-2">
                                        @if ($included)
                                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        @else
                                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        @endif
                                        <span class="{{ $included ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">
                                            {{ $feature->name }}
                                            @if ($isLimit)<span class="font-semibold">— {{ $feature->pivot->value }}</span>@endif
                                        </span>
                                    </li>
                                @endif
                            @empty
                                <li class="text-gray-400">Feature siyahısı boşdur.</li>
                            @endforelse
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
