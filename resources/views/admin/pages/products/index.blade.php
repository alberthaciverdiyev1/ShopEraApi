@extends('admin.layouts.app')

@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4">
            <div class="relative min-w-56 flex-1">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ad / SKU / ID..."
                       hx-get="{{ route('admin.products.index') }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true"
                       hx-trigger="input changed delay:400ms, search"
                       class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm outline-none focus:border-brand-500">
            </div>
            @foreach ([['category_id', $categories, 'Kateqoriya', fn($c) => $c->name], ['brand_id', $brands, 'Brend', fn($b) => $b->name]] as [$filterName, $collection, $placeholder, $label])
                <select name="{{ $filterName }}" hx-get="{{ route('admin.products.index') }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true" hx-trigger="change"
                        class="rounded-lg border border-gray-200 py-2 pl-3 pr-8 text-sm">
                    <option value="">{{ $placeholder }}</option>
                    @foreach ($collection as $option)
                        <option value="{{ $option->id }}" @selected((string) ($filters[$filterName] ?? '') === (string) $option->id)>{{ $label($option) }}</option>
                    @endforeach
                </select>
            @endforeach
            <select name="is_active" hx-get="{{ route('admin.products.index') }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true" hx-trigger="change"
                    class="rounded-lg border border-gray-200 py-2 pl-3 pr-8 text-sm">
                <option value="">Status</option>
                <option value="1" @selected(($filters['is_active'] ?? '') === '1')>Aktiv</option>
                <option value="0" @selected(($filters['is_active'] ?? '') === '0')>Deaktiv</option>
            </select>
            <a href="{{ route('admin.products.prices') }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Toplu qiymət</a>
            <a href="{{ route('admin.products.create') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yeni məhsul</a>
        </div>
        <div id="resource-table">
            @include('admin.pages.products._table')
        </div>
    </div>
@endsection
