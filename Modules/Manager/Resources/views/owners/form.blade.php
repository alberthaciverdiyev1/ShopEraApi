@extends('manager::layouts.app')
@section('content')
@if (session('created_store'))
    @php $cs = session('created_store'); @endphp
    <div class="mb-5 rounded-xl border {{ $cs['provisioned'] ? 'border-emerald-300 bg-emerald-50' : 'border-amber-300 bg-amber-50' }} p-5">
        <p class="mb-3 font-semibold {{ $cs['provisioned'] ? 'text-emerald-800' : 'text-amber-800' }}">
            {{ $cs['provisioned'] ? '✅ Mağaza hazırdır' : '⚠️ Mağaza yaradıldı, amma provision (webhook) alınmadı' }}
        </p>
        <div class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
            <div><span class="text-gray-500">Sayt URL:</span>
                @foreach ($cs['urls'] as $u)<a href="{{ $u }}" target="_blank" class="ml-1 font-medium text-brand-700 hover:underline">{{ $u }}</a>@endforeach
            </div>
            <div><span class="text-gray-500">Baza:</span> <span class="font-mono text-xs">{{ $cs['database'] }}</span></div>
            <div><span class="text-gray-500">Storage:</span> <span class="font-mono text-xs">public/{{ $cs['storage_root'] }}</span></div>
            <div><span class="text-gray-500">Admin e-poçt:</span> <span class="font-medium">{{ $cs['admin_email'] }}</span></div>
            <div><span class="text-gray-500">Admin şifrə:</span> <span class="font-mono font-semibold">{{ $cs['admin_password'] }}</span></div>
            <div class="sm:col-span-2"><span class="text-gray-500">İnteqrasiya tokeni:</span> <span class="font-mono text-xs">{{ $cs['token'] }}</span></div>
            @if (! $cs['provisioned'] && ! empty($cs['provision']))
                <div class="sm:col-span-2 rounded-lg bg-white/70 p-3 text-xs">
                    <div><span class="text-gray-500">Webhook URL:</span> <span class="font-mono">{{ $cs['provision']['url'] ?? '-' }}</span></div>
                    <div><span class="text-gray-500">Status:</span> <span class="font-mono">{{ $cs['provision']['status'] ?? '-' }}</span></div>
                    <div><span class="text-gray-500">Səbəb:</span> <span class="font-mono">{{ $cs['provision']['message'] ?? '-' }}</span></div>
                    @if (! empty($cs['provision']['body']))
                        <pre class="mt-2 max-h-32 overflow-auto whitespace-pre-wrap rounded bg-gray-950 p-2 text-[11px] text-gray-100">{{ $cs['provision']['body'] }}</pre>
                    @endif
                </div>
            @endif
        </div>
        <p class="mt-3 text-xs text-gray-500">Bu məlumatları kopyala və saxla — şifrə yalnız bir dəfə göstərilir.</p>
    </div>
@endif


    @php
        $isEdit = (bool) $owner;
        $action = $isEdit ? route('manager.owners.update', $owner) : route('manager.owners.store');
        $sub = $owner?->currentSubscription;
        $hosts = $owner ? $owner->domains->pluck('host')->all() : [];
        $input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500';
        $label = 'mb-1 block text-sm font-medium text-gray-700';
    @endphp

    <form method="POST" action="{{ $action }}" class="space-y-5 pb-16">
        @csrf @if ($isEdit) @method('PUT') @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                {{-- Owner --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-800">Sahib məlumatı</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="{{ $label }}">Ad</label><input name="name" value="{{ old('name', $owner?->name) }}" required class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">Şirkət</label><input name="company" value="{{ old('company', $owner?->company) }}" class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">E-poçt</label><input type="email" name="email" value="{{ old('email', $owner?->email) }}" required class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">Telefon</label><input name="phone" value="{{ old('phone', $owner?->phone) }}" class="{{ $input }}"></div>
                        <div>
                            <label class="{{ $label }}">Status</label>
                            <select name="status" class="{{ $input }}">
                                @foreach (['active' => 'Aktiv', 'trial' => 'Sınaq', 'suspended' => 'Dayandırılıb', 'cancelled' => 'Ləğv edilib'] as $val => $text)
                                    <option value="{{ $val }}" @selected(old('status', $owner?->status?->value ?? 'trial') === $val)>{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2"><label class="{{ $label }}">Qeyd</label><textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes', $owner?->notes) }}</textarea></div>
                    </div>
                </div>

                {{-- Feature entitlements --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-1 font-semibold text-gray-800">Feature-lar</p>
                    <p class="mb-4 text-xs text-gray-500">Boş = plan/default. Override planı üstələyir.</p>
                    @foreach ($groups as $groupName => $features)
                        <div class="mb-4">
                            <p class="mb-2 text-xs font-bold uppercase text-gray-400">{{ $groupName ?? 'Digər' }}</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach ($features as $feature)
                                    @php $eff = $effective[$feature->key] ?? null; $ov = old("overrides.{$feature->id}", $overrides[$feature->id] ?? ''); @endphp
                                    <div class="rounded-lg border border-gray-100 p-3">
                                        <div class="mb-1 flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-700">{{ $feature->name }}</span>
                                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase text-gray-500">{{ $eff['source'] ?? 'default' }}</span>
                                        </div>
                                        @if ($feature->type->value === 'bool')
                                            <select name="overrides[{{ $feature->id }}]" class="{{ $input }}">
                                                <option value="">— Plan/default ({{ ($eff['value'] ?? '0') === '1' ? 'açıq' : 'bağlı' }}) —</option>
                                                <option value="1" @selected((string) $ov === '1')>Açıq</option>
                                                <option value="0" @selected((string) $ov === '0')>Bağlı</option>
                                            </select>
                                        @else
                                            <input type="number" name="overrides[{{ $feature->id }}]" value="{{ $ov }}" placeholder="default: {{ $eff['value'] ?? '—' }}" class="{{ $input }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-5">
                {{-- Domains --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-800">Domenlər</p>
                    <p class="mb-3 text-xs text-gray-500">Boş buraxılsa avtomatik subdomain yaradılır. Birinci domen əsas sayılır (məs. redbull.shopera.test).</p>
                    @for ($i = 0; $i < max(3, count($hosts)); $i++)
                        <input name="domains[{{ $i }}]" value="{{ old("domains.$i", $hosts[$i] ?? '') }}"
                               placeholder="subdomain.{{ config('manager.base_domain') }}" class="{{ $input }} mb-2">
                    @endfor
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-2 font-semibold text-gray-800">İnteqrasiya</p>
                    <label class="{{ $label }}">Instance URL</label>
                    <input name="instance_url" value="{{ old('instance_url', $owner?->instance_url) }}" placeholder="https://magaza.shopera.test" class="{{ $input }}">
                    <p class="mt-2 text-xs text-gray-500">Manager dəyişiklikləri bu ünvana webhook ilə göndərir.</p>
                </div>

{{-- Theme --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-800">Tema</p>
                    <select name="theme_id" class="{{ $input }}">
                        <option value="">— Default tema —</option>
                        @foreach ($themes as $theme)
                            <option value="{{ $theme->id }}" @selected((string) old('theme_id', $owner?->theme_id) === (string) $theme->id)>{{ $theme->name }}{{ $theme->is_default ? ' (default)' : '' }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Sahibin öz paneli də bu siyahıdan seçə bilər (API).</p>
                </div>

                {{-- Subscription --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-800">Abunəlik</p>
                    <label class="{{ $label }}">Plan</label>
                    <select name="plan_id" class="{{ $input }} mb-3">
                        <option value="">— Plan seçilməyib —</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('plan_id', $sub?->plan_id) === (string) $plan->id)>{{ $plan->name }} — {{ number_format((float) $plan->price, 0) }} ₼</option>
                        @endforeach
                    </select>
                    <label class="{{ $label }}">Status</label>
                    <select name="sub_status" class="{{ $input }} mb-3">
                        @foreach (['trialing' => 'Sınaq', 'active' => 'Aktiv', 'past_due' => 'Gecikmiş', 'cancelled' => 'Ləğv', 'expired' => 'Bitib'] as $val => $text)
                            <option value="{{ $val }}" @selected(old('sub_status', $sub?->status?->value ?? 'active') === $val)>{{ $text }}</option>
                        @endforeach
                    </select>
                    <label class="{{ $label }}">Qiymət</label>
                    <input type="number" step="0.01" name="sub_price" value="{{ old('sub_price', $sub?->price) }}" class="{{ $input }} mb-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="{{ $label }}">Başlama</label><input type="date" name="sub_starts" value="{{ old('sub_starts', $sub?->starts_at?->format('Y-m-d')) }}" class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">Bitmə</label><input type="date" name="sub_ends" value="{{ old('sub_ends', $sub?->ends_at?->format('Y-m-d')) }}" class="{{ $input }}"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-10 border-t border-gray-200 bg-white/95 px-5 py-3 backdrop-blur">
            <div class="mx-auto flex max-w-6xl justify-end gap-2">
                <a href="{{ route('manager.owners.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Ləğv et</a>
                <button class="rounded-lg bg-brand-600 px-6 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ $isEdit ? 'Yadda saxla' : 'Yarat' }}</button>
            </div>
        </div>
    </form>

    @if ($isEdit)
        <div class="mx-auto max-w-6xl rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="mb-3 font-semibold text-gray-800">İnteqrasiya açarları</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">API token (MANAGER_TOKEN)</label>
                    <div class="flex gap-2">
                        <input readonly value="{{ $owner->api_token }}" class="block w-full rounded-lg border border-gray-300 bg-gray-100 p-2.5 font-mono text-xs">
                        <form method="POST" action="{{ route('manager.owners.token', $owner) }}">
                            @csrf
                            <button class="whitespace-nowrap rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50" onclick="return confirm('Köhnə token işləməyəcək. Yenilənsin?')">Yenilə</button>
                        </form>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Webhook secret (MANAGER_WEBHOOK_SECRET)</label>
                    <div class="flex gap-2">
                        <input readonly value="{{ $owner->webhook_secret }}" class="block w-full rounded-lg border border-gray-300 bg-gray-100 p-2.5 font-mono text-xs">
                        <form method="POST" action="{{ route('manager.owners.secret', $owner) }}">
                            @csrf
                            <button class="whitespace-nowrap rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50" onclick="return confirm('Webhook secret yenilənsin?')">Yenilə</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
