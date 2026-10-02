@extends('admin.layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-800">Bildiriş göndər</h2>
        <form method="POST" action="{{ route('admin.notifications.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Başlıq</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                @error('title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Mətn</label>
                <textarea name="body" rows="4" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">{{ old('body') }}</textarea>
                @error('body')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Link (URL)</label>
                <input type="text" name="url" value="{{ old('url') }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Şəkil</label>
                <input type="file" name="image" accept="image/*" class="block w-full text-sm text-gray-500">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="all" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-600">
                Bütün istifadəçilərə göndər
            </label>
            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                <a href="{{ route('admin.notifications.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Ləğv et</a>
                <button class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">Göndər</button>
            </div>
        </form>
    </div>
@endsection
