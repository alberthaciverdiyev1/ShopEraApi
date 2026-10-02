@extends('admin.layouts.app')

@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4">
            <div class="relative min-w-56 flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -trangray-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Kateqoriya axtar..."
                       hx-get="{{ route($route.'.index') }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true"
                       hx-trigger="input changed delay:400ms, search"
                       class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-brand-500 focus:ring-brand-500">
            </div>
            <button type="button"
                    hx-get="{{ route($route.'.create') }}" hx-target="#modal-root" hx-swap="innerHTML"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                @include('admin.partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4'])
                Yeni
            </button>
        </div>

        <div id="resource-table">
            @include('admin.pages.categories._table')
        </div>
    </div>
@endsection
