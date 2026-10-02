<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                <th class="px-4 py-3 font-semibold">Video</th>
                <th class="px-4 py-3 font-semibold">Məhsul</th>
                <th class="px-4 py-3 font-semibold">Story</th>
                <th class="px-4 py-3 font-semibold">Bitmə</th>
                <th class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($rows as $video)
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3">
                        @if ($video->video_path)
                            <video src="{{ $video->video_path }}" class="h-16 w-16 rounded-lg bg-gray-900 object-cover" muted></video>
                        @else
                            <span class="flex h-16 w-16 items-center justify-center rounded-lg bg-gray-100 text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($video->product)
                            <a href="{{ route('admin.products.show', $video->product_id) }}" class="font-medium text-gray-700 hover:text-brand-600">
                                {{ $video->product->title ?? '#'.$video->product_id }}
                            </a>
                        @else
                            <span class="text-gray-400">#{{ $video->product_id }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($video->is_story_hidden)
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">Deaktiv</span>
                        @else
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Aktiv</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $video->story_expires_at?->format('d.m.Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-1">
                            @if ($video->is_story_hidden)
                                <form method="POST" action="{{ route('admin.story-videos.activate', $video->id) }}">
                                    @csrf
                                    <button class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Aktivləşdir</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.story-videos.deactivate', $video->id) }}">
                                    @csrf
                                    <button class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200">Deaktiv et</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.story-videos.destroy', $video->id) }}">
                                @csrf @method('DELETE')
                                <button class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600" onclick="return confirm('Video silinsin?')">@include('admin.partials.icon', ['name' => 'trash', 'class' => 'h-4 w-4'])</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">Video yoxdur</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('admin.partials.pagination', ['rows' => $rows])
