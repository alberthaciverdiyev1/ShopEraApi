@extends('manager::layouts.app')
@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-200 p-4">
            <p class="font-semibold text-gray-700">Promo bloklar <span class="text-xs text-gray-400">(yalnız Free plan instansiyalarında göstərilir)</span></p>
            <a href="{{ route('manager.promo-blocks.create') }}" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Yeni blok</a>
        </div>
        <table class="w-full text-left text-sm text-gray-500">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr><th class="px-4 py-3 font-semibold">Şəkil</th><th class="px-4 py-3 font-semibold">Tip</th><th class="px-4 py-3 font-semibold">Başlıq</th><th class="px-4 py-3 font-semibold">Status</th><th class="px-4 py-3 text-right font-semibold">Əməliyyat</th></tr>
            </thead>
            <tbody>
                @forelse ($blocks as $block)
                    <tr class="border-b border-gray-100 bg-white hover:bg-gray-50">
                        <td class="px-4 py-3">
                            @if ($block->image)<img src="{{ $block->image }}" class="h-10 w-16 rounded object-cover ring-1 ring-gray-200">
                            @else <span class="text-gray-300">—</span> @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded px-2 py-0.5 text-xs font-medium {{ $block->type === 'ad' ? 'bg-amber-50 text-amber-700' : 'bg-brand-50 text-brand-700' }}">
                                {{ $block->type === 'ad' ? 'Reklam' : 'Offer' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $block->title }}<p class="text-xs text-gray-400">{{ $block->subtitle }}</p></td>
                        <td class="px-4 py-3"><span class="{{ $block->is_active ? 'text-emerald-600' : 'text-gray-400' }}">{{ $block->is_active ? 'Aktiv' : 'Deaktiv' }}</span></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('manager.promo-blocks.edit', $block) }}" class="rounded-lg px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50">Redaktə</a>
                                <form method="POST" action="{{ route('manager.promo-blocks.destroy', $block) }}">@csrf @method('DELETE')
                                    <button class="rounded-lg px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50" onclick="return confirm('Silinsin?')">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">Blok yoxdur</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
