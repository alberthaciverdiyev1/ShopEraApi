@extends('admin.layouts.app')

@section('content')
    <div class="space-y-5">
        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <header class="flex items-center justify-between gap-3 border-b border-gray-100 bg-gray-50/60 px-5 py-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903m0 0V4.5m0 4.098h-4.098M19.245 13.941a7.5 7.5 0 01-12.548 3.364l-1.903-1.903m0 0V19.5m0-4.098h4.098"/>
                        </svg>
                    </span>
                    <h2 class="font-semibold text-gray-800">CJ Dropshipping</h2>
                </div>
                <span @class([
                    'rounded-full px-3 py-1 text-xs font-semibold',
                    'bg-green-50 text-green-700' => $configured,
                    'bg-amber-50 text-amber-700' => ! $configured,
                ])>
                    {{ $configured ? 'API açarı qurulub' : 'API açarı yoxdur' }}
                </span>
            </header>

            <div class="p-5">
                <p class="text-sm text-gray-500">
                    Provider məlumatlarını əl ilə sinxronlaşdırın. Hər resurs üçün ayrı-ayrı və ya
                    hamısını birlikdə işə salın. Heç bir avtomatik scheduler yoxdur — yalnız buradan tetiklenir.
                </p>

                @if (! $configured)
                    <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Əvvəlcə <a href="{{ route('admin.settings.index') }}" class="font-semibold underline">Parametrlər</a>
                        səhifəsindən CJ API açarını yazın.
                    </p>
                @endif
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <header class="border-b border-gray-100 bg-gray-50/60 px-5 py-3">
                <h2 class="font-semibold text-gray-800">Resurslar</h2>
            </header>

            <div class="divide-y divide-gray-100">
                @foreach ($actions as $key => $action)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800">{{ $action['label'] }}</p>
                            <p class="text-sm text-gray-500">{{ $action['description'] }}</p>
                        </div>

                        <form method="POST" action="{{ route($action['route']) }}" class="flex items-center gap-3">
                            @csrf
                            <label class="flex items-center gap-1.5 text-xs text-gray-500">
                                <input type="hidden" name="translate" value="0">
                                <input type="checkbox" name="translate" value="1" class="rounded border-gray-300">
                                Tərcümə et (yavaş)
                            </label>
                            <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                                Sinxronla
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                <div>
                    <p class="font-medium text-gray-800">Hamısını sinxronla</p>
                    <p class="text-sm text-gray-500">Yuxarıdaki bütün resursları ardıcıl işə salır.</p>
                </div>

                <form method="POST" action="{{ route('admin.cj-dropshipping.sync-all') }}" class="flex items-center gap-3">
                    @csrf
                    <label class="flex items-center gap-1.5 text-xs text-gray-500">
                        <input type="hidden" name="translate" value="0">
                        <input type="checkbox" name="translate" value="1" class="rounded border-gray-300">
                        Tərcümə et (yavaş)
                    </label>
                    <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Hamısını sinxronla
                    </button>
                </form>
            </div>
        </section>

        @if (! empty($lastResult))
            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <header class="border-b border-gray-100 bg-gray-50/60 px-5 py-3">
                    <h2 class="font-semibold text-gray-800">Son nəticə</h2>
                </header>
                <div class="p-5 text-sm text-gray-700">
                    <pre class="overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs">{{ json_encode($lastResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </section>
        @endif
    </div>
@endsection
