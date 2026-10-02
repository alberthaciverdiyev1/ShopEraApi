@extends('manager::layouts.app')
@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <a href="{{ $stat['route'] }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md">
                <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-gray-800">{{ $stat['value'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                <p class="font-semibold text-gray-700">Son sahiblər</p>
                <a href="{{ route('manager.owners.index') }}" class="text-sm text-brand-600 hover:underline">Hamısı</a>
            </div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recentOwners as $owner)
                        <tr>
                            <td class="px-5 py-3">
                                <a href="{{ route('manager.owners.edit', $owner) }}" class="font-medium text-gray-800 hover:text-brand-600">{{ $owner->name }}</a>
                                <p class="text-xs text-gray-400">{{ $owner->primaryDomain()?->host ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $owner->currentSubscription?->plan?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">{{ $owner->status->label() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-8 text-center text-gray-400">Sahib yoxdur</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-3 font-semibold text-gray-700">Planlar</div>
            <div class="divide-y divide-gray-100">
                @foreach ($plans as $plan)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $plan->name }}</p>
                            <p class="text-xs text-gray-400">{{ $plan->features_count }} feature</p>
                        </div>
                        <span class="text-sm font-semibold text-gray-800">{{ number_format((float) $plan->price, 0) }} ₼</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
