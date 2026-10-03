@extends('admin.layouts.app')

@section('content')
    @php $current = $order->latestStatus?->status; @endphp

    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('admin.orders.index') }}" class="rounded-lg border border-gray-200 bg-white p-2 text-gray-500 hover:bg-gray-50">@include('admin.partials.icon', ['name' => 'back', 'class' => 'h-4 w-4'])</a>
        <h2 class="text-lg font-semibold text-gray-800">Sifariş #{{ $order->id }}</h2>
        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">{{ $current?->label() ?? '—' }}</span>
        <form method="POST" action="{{ route('admin.orders.destroy', $order->id) }}" class="ml-auto">
            @csrf @method('DELETE')
            <button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50" onclick="return confirm('Silinsin?')">Sifarişi sil</button>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3 font-semibold text-gray-700">Məhsullar</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase text-gray-400">
                            <th class="px-5 py-2 font-semibold">Məhsul</th>
                            <th class="px-5 py-2 font-semibold">Qiymət</th>
                            <th class="px-5 py-2 font-semibold">Say</th>
                            <th class="px-5 py-2 text-right font-semibold">Cəm</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($item->product?->images->first())
                                            <img src="{{ $item->product->images->first()->image_path }}" class="h-10 w-10 rounded-lg object-cover ring-1 ring-gray-200">
                                        @endif
                                        <div>
                                            <a href="{{ route('admin.products.show', $item->product_id) }}" class="font-medium text-gray-700 hover:text-brand-600">
                                                {{ $item->product?->title ?? '—' }}
                                            </a>
                                            <p class="text-xs text-gray-400">{{ $item->product?->category?->name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">{{ number_format((float) $item->price, 2) }} ₼</td>
                                <td class="px-5 py-3">{{ $item->quantity }}</td>
                                <td class="px-5 py-3 text-right font-medium">{{ number_format((float) ($item->price * $item->quantity), 2) }} ₼</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-100">
                            <td colspan="3" class="px-5 py-3 text-right text-gray-500">Çatdırılma</td>
                            <td class="px-5 py-3 text-right">{{ number_format((float) $order->shipping_price, 2) }} ₼</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-right font-semibold text-gray-700">Ümumi</td>
                            <td class="px-5 py-3 text-right text-lg font-bold text-gray-800">{{ number_format((float) $order->total_price, 2) }} ₼</td>
                        </tr>
                    </tfoot>
                </table>
                </div>

            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Status tarixçəsi</p>
                <ol class="space-y-3">
                    @foreach ($order->statuses as $entry)
                        <li class="flex items-center gap-3">
                            <span class="h-2.5 w-2.5 rounded-full bg-brand-500"></span>
                            <span class="text-sm text-gray-700">{{ $entry->label ?? $entry->status?->label() }}</span>
                            <span class="ml-auto text-xs text-gray-400">{{ $entry->created_at?->format('d.m.Y H:i') }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Statusu dəyiş</p>
                <form method="POST" action="{{ route('admin.orders.status', $order->id) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <select name="status" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($current === $status)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="return_to_balance" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-600">
                        Ləğvdə balansa qaytar
                    </label>
                    <button class="w-full rounded-lg bg-brand-600 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
                </form>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Müştəri</p>
                @if ($order->user)
                    <a href="{{ route('admin.users.show', $order->user->id) }}" class="font-medium text-brand-600 hover:underline">
                        {{ $order->user->name }} {{ $order->user->surname }}
                    </a>
                    <p class="mt-1 text-sm text-gray-500">{{ $order->user->phone }}</p>
                    <p class="text-sm text-gray-500">{{ $order->user->email }}</p>
                @else
                    <p class="text-sm text-gray-400">—</p>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 font-semibold text-gray-700">Ödəniş / çatdırılma</p>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Ödəniş tipi</dt><dd class="text-gray-700">{{ $order->payment_type ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Çatdırılma</dt><dd class="text-gray-700">{{ $order->address?->city ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Ünvan</dt><dd class="max-w-40 truncate text-gray-700" title="{{ $order->address?->address }}">{{ $order->address?->address ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Tarix</dt><dd class="text-gray-700">{{ $order->created_at?->format('d.m.Y H:i') }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
@endsection
