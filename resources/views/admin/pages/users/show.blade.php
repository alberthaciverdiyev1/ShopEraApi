@extends('admin.layouts.app')

@section('content')
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-gray-200 bg-white p-2 text-gray-500 hover:bg-gray-50">@include('admin.partials.icon', ['name' => 'back', 'class' => 'h-4 w-4'])</a>
        <h2 class="text-lg font-semibold text-gray-800">{{ $title }}</h2>
        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="ml-auto">
            @csrf @method('DELETE')
            <button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50" onclick="return confirm('İstifadəçi silinsin?')">Sil</button>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-100 text-lg font-bold text-brand-700">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                <div>
                    <p class="font-semibold text-gray-800">{{ trim($user->name.' '.$user->surname) ?: '—' }}</p>
                    <p class="text-xs text-gray-400">ID #{{ $user->id }}</p>
                </div>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Telefon</dt><dd class="text-gray-700">{{ $user->phone ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">E-poçt</dt><dd class="truncate text-gray-700">{{ $user->email ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Sifarişlər</dt><dd class="text-gray-700">{{ $ordersCount }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Qeydiyyat</dt><dd class="text-gray-700">{{ $user->created_at?->format('d.m.Y') }}</dd></div>
            </dl>

            <div class="mt-4 space-y-2 border-t border-gray-100 pt-4">
                <form method="POST" action="{{ route('admin.users.status', $user->id) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                    <button class="w-full rounded-lg border px-3 py-2 text-sm font-medium {{ $user->is_active ? 'border-rose-200 text-rose-600 hover:bg-rose-50' : 'border-emerald-200 text-emerald-600 hover:bg-emerald-50' }}">
                        {{ $user->is_active ? 'Blokla' : 'Blokdan çıxar' }}
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Rollar</p>
                <form method="POST" action="{{ route('admin.users.roles', $user->id) }}" class="flex flex-wrap items-end gap-3">
                    @csrf @method('PUT')
                    <div class="flex-1">
                        <select name="roles[]" multiple size="5" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected($user->roles->contains('name', $role))>{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yenilə</button>
                </form>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Şifrə dəyiş</p>
                <form method="POST" action="{{ route('admin.users.password', $user->id) }}" class="grid grid-cols-2 gap-3">
                    @csrf @method('PUT')
                    <input type="password" name="password" placeholder="Yeni şifrə" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <input type="password" name="password_confirmation" placeholder="Təkrar" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <button class="col-span-2 rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900">Şifrəni yenilə</button>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3 font-semibold text-gray-700">Son sifarişlər</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td class="px-5 py-3"><a href="{{ route('admin.orders.show', $order->id) }}" class="font-medium text-brand-600 hover:underline">#{{ $order->id }}</a></td>
                                <td class="px-5 py-3">{{ number_format((float) $order->total_price, 2) }} ₼</td>
                                <td class="px-5 py-3 text-gray-500">{{ $order->created_at?->format('d.m.Y') }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-5 py-8 text-center text-gray-400">Sifariş yoxdur</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

            </div>
        </div>
    </div>
@endsection
