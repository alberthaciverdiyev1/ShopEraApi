@extends('manager::layouts.app')
@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm text-gray-500">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr><th class="px-4 py-3 font-semibold">Plan</th><th class="px-4 py-3 font-semibold">Qiymət</th><th class="px-4 py-3 font-semibold">Dövr</th><th class="px-4 py-3 font-semibold">Feature</th><th class="px-4 py-3 font-semibold">Abunə</th><th class="px-4 py-3 text-right font-semibold">Əməliyyat</th></tr>
            </thead>
            <tbody>
                @foreach ($plans as $plan)
                    <tr class="border-b border-gray-100 bg-white hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $plan->name }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $plan->price, 2) }} ₼</td>
                        <td class="px-4 py-3">{{ $plan->billing_cycle->label() }}</td>
                        <td class="px-4 py-3">{{ $plan->features_count }}</td>
                        <td class="px-4 py-3">{{ $plan->subscriptions_count }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('manager.plans.edit', $plan) }}" class="rounded-lg px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50">Redaktə</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
