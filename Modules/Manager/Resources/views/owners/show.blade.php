@extends('manager::layouts.app')
@section('content')
    @php
        $usageCards = [
            ['Məhsul', $owner->usage_products ?? 0],
            ['Kateqoriya', $owner->usage_categories ?? 0],
            ['Sifariş', $owner->usage_orders ?? 0],
            ['Müştəri', $owner->usage_customers ?? 0],
            ['İşçi', $owner->usage_staff ?? 0],
            ['Yaddaş', number_format((float) ($owner->usage_storage_mb ?? 0), 2).' MB'],
            ['Baza', number_format((float) ($owner->usage_db_mb ?? 0), 2).' MB'],
        ];
        $fmt = fn ($d) => $d ? $d->format('d.m.Y H:i') : '—';
    @endphp

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <a href="{{ route('manager.owners.index') }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">← Geri</a>
        <h1 class="text-lg font-bold text-gray-800">{{ $owner->name }}</h1>
        <span class="rounded bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">{{ $owner->status->label() }}</span>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('manager.owners.edit', $owner) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Redaktə</a>
            <form method="POST" action="{{ route('manager.owners.push', $owner) }}">
                @csrf
                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">⟳ DB sync</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Owner / subscription --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="mb-3 font-semibold text-gray-700">Hesab</p>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-400">E-poçt</dt><dd class="text-gray-700">{{ $owner->email ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Plan</dt><dd class="text-gray-700">{{ $owner->currentSubscription?->plan?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Qiymət</dt><dd class="text-gray-700">{{ $owner->currentSubscription?->price !== null ? number_format((float) $owner->currentSubscription->price, 0).' ₼' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Baza</dt><dd class="text-gray-700">{{ $owner->db_name ?? '—' }}</dd></div>
                <div>
                    <dt class="text-gray-400">Domenlər</dt>
                    <dd class="mt-1 flex flex-wrap gap-1">
                        @forelse ($owner->domains as $domain)
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $domain->host }}</span>
                        @empty
                            <span class="text-gray-400">—</span>
                        @endforelse
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Activity times --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="mb-3 font-semibold text-gray-700">Giriş / fəaliyyət</p>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-400">Son giriş</dt><dd class="text-gray-700">{{ $fmt($owner->last_login_at) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Son çıxış</dt><dd class="text-gray-700">{{ $fmt($owner->last_logout_at) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Son aktivlik</dt><dd class="text-gray-700">{{ $fmt($owner->last_seen_at) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Son əməliyyat</dt><dd class="truncate text-gray-700" title="{{ $owner->last_action }}">{{ $owner->last_action ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Usage yenilənib</dt><dd class="text-gray-700">{{ $fmt($owner->usage_reported_at) }}</dd></div>
            </dl>
        </div>

        {{-- Data usage --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="mb-3 font-semibold text-gray-700">Data istifadəsi</p>
            <div class="grid grid-cols-2 gap-3">
                @foreach ($usageCards as [$label, $value])
                    <div class="rounded-lg bg-gray-50 p-3">
                        <p class="text-xs text-gray-400">{{ $label }}</p>
                        <p class="text-lg font-bold text-gray-800">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Activity log --}}
    <div class="mt-4 rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-3 font-semibold text-gray-700">Fəaliyyət jurnalı</div>
        <div class="max-h-[28rem] overflow-y-auto">
            @forelse ($owner->activities as $activity)
                <div class="flex items-center gap-3 border-b border-gray-50 px-5 py-2.5 text-sm">
                    <span class="w-40 shrink-0 text-xs text-gray-400">{{ $activity->created_at?->format('d.m.Y H:i') }}</span>
                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $activity->action }}</span>
                    <span class="truncate text-gray-500">{{ $activity->description }}</span>
                    <span class="ml-auto shrink-0 text-xs text-gray-400">{{ $activity->ip }}</span>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-gray-400">Hələ fəaliyyət qeydə alınmayıb.</p>
            @endforelse
        </div>
    </div>
@endsection
