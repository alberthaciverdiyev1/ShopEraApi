@extends('manager::layouts.app')
@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-200 p-4">
            <p class="font-semibold text-gray-700">Feature kataloqu</p>
            <button hx-get="{{ route('manager.features.create') }}" hx-target="#modal-root" hx-swap="innerHTML"
                    class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Yeni feature</button>
        </div>
        <div id="feature-table">@include('manager::features._table')</div>
    </div>
@endsection
