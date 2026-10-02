@extends('admin.layouts.app')

@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4">
            <div class="relative min-w-60 flex-1">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Komanda üzvü axtar..."
                       hx-get="{{ route('admin.team.index') }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true"
                       hx-trigger="input changed delay:400ms, search"
                       class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm outline-none focus:border-brand-500">
            </div>
        </div>
        <div id="resource-table">
            @include('admin.pages.team._table')
        </div>
    </div>
@endsection
