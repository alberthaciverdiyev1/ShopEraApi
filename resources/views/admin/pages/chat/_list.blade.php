@forelse ($conversations as $conversation)
    @php $isActive = isset($active) && $active && $active->id === $conversation->id; @endphp
    <a href="{{ route('admin.chat.show', $conversation->id) }}"
       class="flex items-center gap-3 border-b border-gray-50 px-4 py-3 hover:bg-gray-50 {{ $isActive ? 'bg-brand-50' : '' }}">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-600">
            {{ strtoupper(substr($conversation->user?->name ?? 'U', 0, 1)) }}
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-gray-700">{{ trim($conversation->user?->name.' '.$conversation->user?->surname) ?: '—' }}</p>
            <p class="truncate text-xs text-gray-400">
                @if ($conversation->last_message_at)
                    {{ \Illuminate\Support\Carbon::parse($conversation->last_message_at)->diffForHumans() }}
                @endif
            </p>
        </div>
        @if (($conversation->unread_count ?? 0) > 0)
            <span class="rounded-full bg-brand-600 px-2 py-0.5 text-xs font-semibold text-white">{{ $conversation->unread_count }}</span>
        @endif
    </a>
@empty
    <p class="px-4 py-10 text-center text-sm text-gray-400">Söhbət yoxdur</p>
@endforelse

@if (method_exists($conversations, 'links'))
    <div class="p-3">@include('admin.partials.pagination', ['rows' => $conversations])</div>
@endif
