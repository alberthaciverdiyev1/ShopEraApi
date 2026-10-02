<div class="flex h-[70vh] flex-col">
    <div class="flex-1 space-y-3 overflow-y-auto bg-gray-50/60 p-4">
        @forelse ($messages as $message)
            @php $mine = $message->sender_type === 'admin'; @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[70%] rounded-lg px-3.5 py-2 text-sm shadow-sm {{ $mine ? 'bg-brand-600 text-white' : 'bg-white text-gray-700' }}">
                    @if ($message->message)
                        <p class="whitespace-pre-line">{{ $message->message }}</p>
                    @endif
                    @foreach ($message->attachments as $attachment)
                        <img src="{{ $attachment->path }}" class="mt-2 max-h-48 rounded-lg">
                    @endforeach
                    <div class="mt-1 flex items-center gap-2 text-[10px] {{ $mine ? 'text-white/70' : 'text-gray-400' }}">
                        <span>{{ $message->created_at?->format('d.m H:i') }}</span>
                        @if ($mine)
                            <button type="button" hx-delete="{{ route('admin.chat.message.destroy', $message->id) }}"
                                    hx-target="#chat-thread" hx-swap="none"
                                    hx-confirm="Mesaj silinsin?" class="hover:text-white">sil</button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-sm text-gray-400">Mesaj yoxdur</p>
        @endforelse
    </div>

    <form hx-post="{{ route('admin.chat.send', $active->id) }}" hx-target="#chat-thread" hx-swap="innerHTML"
          hx-encoding="multipart/form-data"
          class="flex items-center gap-2 border-t border-gray-100 p-3">
        <input type="file" name="image" accept="image/*" class="w-32 text-xs text-gray-400">
        <input type="text" name="message" placeholder="Mesaj yaz..." autocomplete="off"
               class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Göndər</button>
    </form>
</div>
