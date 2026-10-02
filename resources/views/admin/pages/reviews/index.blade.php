@extends('admin.layouts.app')

@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4">
            <div class="relative min-w-60 flex-1">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Şərh / məhsul axtar..."
                       hx-get="{{ route('admin.reviews.index') }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true"
                       hx-trigger="input changed delay:400ms, search"
                       class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm outline-none focus:border-brand-500 focus:ring-brand-500">
            </div>
            <select name="status"
                    hx-get="{{ route('admin.reviews.index') }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true" hx-trigger="change"
                    class="rounded-lg border border-gray-200 py-2 pl-3 pr-8 text-sm outline-none focus:border-brand-500">
                <option value="">Bütün statuslar</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected((string) ($filters['status'] ?? '') === (string) $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div id="resource-table">
            @include('admin.pages.reviews._table')
        </div>
    </div>
@endsection
