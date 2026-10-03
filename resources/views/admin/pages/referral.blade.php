@extends('admin.layouts.app')

@section('content')
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.referral.update') }}" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            @csrf @method('PUT')
            <h2 class="font-semibold text-gray-800">Referal parametrləri</h2>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Mükafat məbləği</label>
                <input type="number" step="0.01" name="referral_amount" value="{{ old('referral_amount', $setting->referral_amount ?? 0) }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" @checked($setting->is_active ?? false) class="h-4 w-4 rounded border-gray-300 text-brand-600">
                Referal sistemi aktivdir
            </label>
            <button class="w-full rounded-lg bg-brand-600 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
        </form>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-gray-100 px-5 py-3 font-semibold text-gray-700">Referal kodlar</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase text-gray-400">
                        <th class="px-5 py-2 font-semibold">İstifadəçi</th>
                        <th class="px-5 py-2 font-semibold">Kod</th>
                        <th class="px-5 py-2 font-semibold">Dəvət edilənlər</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($referrals as $referral)
                        <tr>
                            <td class="px-5 py-3">{{ trim($referral->user?->name.' '.$referral->user?->surname) ?: '—' }}</td>
                            <td class="px-5 py-3 font-mono text-gray-600">{{ $referral->referral_code }}</td>
                            <td class="px-5 py-3">{{ $referral->referredUsers->count() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-gray-400">Məlumat yoxdur</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>

        </div>
    </div>
@endsection
