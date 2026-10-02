@extends('admin.layouts.app')

@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 p-4 dark:border-gray-700">
            @if ($searchable)
                <div class="relative min-w-56 flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 dark:text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </div>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Axtar..."
                           hx-get="{{ route($route.'.index') }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true"
                           hx-trigger="input changed delay:400ms, search"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-9 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                </div>
            @else
                <div class="flex-1"></div>
            @endif

            @if ($activeColumn)
                <select name="is_active" hx-get="{{ route($route.'.index') }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true" hx-trigger="change"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Bütün statuslar</option>
                    <option value="1" @selected(($filters['is_active'] ?? '') === '1')>Aktiv</option>
                    <option value="0" @selected(($filters['is_active'] ?? '') === '0')>Deaktiv</option>
                </select>
            @endif

            @if (Route::has($route.'.create'))
                <button type="button" hx-get="{{ route($route.'.create') }}" hx-target="#modal-root" hx-swap="innerHTML"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4'])
                    Yeni
                </button>
            @endif
        </div>

        <div id="resource-table">
            @include('admin.resources._table')
        </div>
    </div>
@endsection
