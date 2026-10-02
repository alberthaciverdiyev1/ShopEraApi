@extends('manager::layouts.app')
@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 p-4">
            <div class="relative min-w-56 flex-1">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ad, e-poçt, domen axtar..."
                       hx-get="{{ route('manager.owners.index') }}" hx-target="#owner-table" hx-swap="innerHTML" hx-push-url="true"
                       hx-trigger="input changed delay:400ms, search"
                       class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <a href="{{ route('manager.owners.create') }}" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Yeni sahib</a>
        </div>
        <div id="owner-table">@include('manager::owners._table')</div>
    </div>
@endsection
