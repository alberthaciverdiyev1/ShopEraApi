@extends('admin.layouts.app')

@section('content')
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[22rem_1fr]">
        <div class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-3">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Müştəri axtar..."
                       hx-get="{{ route('admin.chat.index') }}" hx-target="#chat-list" hx-swap="innerHTML"
                       hx-trigger="input changed delay:400ms, search"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            </div>
            <div id="chat-list" class="max-h-[70vh] overflow-y-auto">
                @include('admin.pages.chat._list')
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            @if ($active)
                <div class="border-b border-gray-100 px-5 py-3">
                    <p class="font-semibold text-gray-800">{{ trim($active->user?->name.' '.$active->user?->surname) }}</p>
                    <p class="text-xs text-gray-400">{{ $active->user?->phone }}</p>
                </div>
                <div id="chat-thread"
                     hx-get="{{ route('admin.chat.show', $active->id) }}" hx-trigger="every 15s" hx-target="#chat-thread" hx-swap="innerHTML">
                    @include('admin.pages.chat._messages')
                </div>
            @else
                <div class="flex h-[70vh] items-center justify-center text-gray-400">Söhbət seçin</div>
            @endif
        </div>
    </div>
@endsection
