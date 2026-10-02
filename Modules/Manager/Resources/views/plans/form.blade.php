@extends('manager::layouts.app')
@section('content')
    @php $input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-brand-500 focus:ring-brand-500'; @endphp
    <form method="POST" action="{{ route('manager.plans.update', $plan) }}" class="space-y-5 pb-16">
        @csrf @method('PUT')
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="mb-4 font-semibold text-gray-800">Plan</p>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Ad</label><input name="name" value="{{ old('name', $plan->name) }}" class="{{ $input }}"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Qiymət</label><input type="number" step="0.01" name="price" value="{{ old('price', $plan->price) }}" class="{{ $input }}"></div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Dövr</label>
                    <select name="billing_cycle" class="{{ $input }}">
                        <option value="monthly" @selected($plan->billing_cycle->value === 'monthly')>Aylıq</option>
                        <option value="yearly" @selected($plan->billing_cycle->value === 'yearly')>İllik</option>
                    </select>
                </div>
                <label class="mt-6 inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($plan->is_active) class="h-4 w-4 rounded border-gray-300 text-brand-600">
                    Aktivdir
                </label>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="mb-4 font-semibold text-gray-800">Feature matrisi</p>
            @foreach ($groups as $groupName => $features)
                <div class="mb-4">
                    <p class="mb-2 text-xs font-bold uppercase text-gray-400">{{ $groupName ?? 'Digər' }}</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($features as $feature)
                            @php $val = old("features.{$feature->id}", $values[$feature->id] ?? ''); @endphp
                            <div class="rounded-lg border border-gray-100 p-3">
                                <span class="mb-1 block text-sm font-medium text-gray-700">{{ $feature->name }}</span>
                                @if ($feature->type->value === 'bool')
                                    <select name="features[{{ $feature->id }}]" class="{{ $input }}">
                                        <option value="0" @selected((string) $val === '0')>Bağlı</option>
                                        <option value="1" @selected((string) $val === '1')>Açıq</option>
                                    </select>
                                @else
                                    <input type="number" name="features[{{ $feature->id }}]" value="{{ $val }}" class="{{ $input }}" placeholder="-1 = limitsiz">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="fixed inset-x-0 bottom-0 z-10 border-t border-gray-200 bg-white/95 px-5 py-3 backdrop-blur">
            <div class="mx-auto flex max-w-6xl justify-end gap-2">
                <a href="{{ route('manager.plans.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Geri</a>
                <button class="rounded-lg bg-brand-600 px-6 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
            </div>
        </div>
    </form>
@endsection
