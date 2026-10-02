@extends('admin.layouts.app')

@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4">
            <div class="relative min-w-60 flex-1">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Başlıq / mətn axtar..."
                       hx-get="{{ route('admin.notifications.index') }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true"
                       hx-trigger="input changed delay:400ms, search"
                       class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm outline-none focus:border-brand-500 focus:ring-brand-500">
            </div>
            <a href="{{ route('admin.notifications.create') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Bildiriş göndər</a>
        </div>
        <div id="resource-table">
            @include('admin.pages.notifications._table')
        </div>
    </div>
@endsection
