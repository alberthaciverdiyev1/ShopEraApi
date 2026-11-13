@php
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };
    $permissionLabel = function (string $key) use ($permissions): string {
        foreach ($permissions as $permission) {
            if (($permission['key'] ?? '') === $key) {
                return $permission['label'] ?? $key;
            }
        }

        return $key;
    };
@endphp

<x-storefront.layouts.app :title="'Komanda — '.$localized($vendor['name'] ?? '').' — ShopEra'">
    <div class="section-padding fix elan-page">
        <div class="container elan-page__narrow">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <h2 class="mb-0">
                    Komanda
                    <span class="text-muted fs-6">— {{ $localized($vendor['name'] ?? '') }}</span>
                </h2>
                <div class="d-flex gap-2">
                    <a class="sf-btn sf-btn--ghost" href="/magaza/panel">Mağaza paneli</a>
                    <a class="theme-btn" href="/elan/ver">Yeni elan</a>
                </div>
            </div>

            @if (session('status'))
                <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="sf-alert sf-alert--error">{{ $errors->first() }}</div>
            @endif

            @if (! empty($invite['url']))
                <div class="sf-panel mb-4" data-sf-invite-result>
                    <h6 class="fw-bold mb-2">Yeni dəvət linki</h6>
                    <p class="text-muted small mb-2">
                        Bu linki əlavə etmək istədiyiniz şəxsə göndərin. Link 7 gün ərzində etibarlıdır.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="text" value="{{ $invite['url'] }}" readonly data-sf-copy-source
                               class="form-control" style="max-width: 520px;">
                        <button type="button" class="sf-btn sf-btn--ghost" data-sf-copy="{{ $invite['url'] }}">Kopyala</button>
                    </div>
                    <p class="text-muted small mb-0 mt-2">
                        İcazələr:
                        @foreach (($invite['permissions'] ?? []) as $key)
                            <span class="sf-badge">{{ $permissionLabel($key) }}</span>
                        @endforeach
                    </p>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-5">
                    <form action="{{ route('storefront.store.team.invite') }}" method="POST" class="sf-panel">
                        @csrf
                        <h6 class="fw-bold mb-3">Üzv dəvət et</h6>
                        <p class="text-muted small">
                            Üzvün mağazada nələri idarə edə biləcəyini seçin. Link yaradıb paylaşın.
                        </p>

                        <label class="d-block mb-3">
                            <span class="d-block mb-1">İcazələr</span>
                            <select name="permissions[]" multiple size="{{ max(3, count($permissions)) }}" required
                                    class="form-select">
                                @foreach ($permissions as $permission)
                                    <option value="{{ $permission['key'] }}"
                                            @selected(in_array($permission['key'], old('permissions', []), true))>
                                        {{ $permission['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-muted small">Birdən çoxunu seçmək üçün Ctrl basıb klikləyin.</span>
                        </label>

                        <button type="submit" class="theme-btn">Dəvət linki yarat</button>
                    </form>
                </div>

                <div class="col-lg-7">
                    <div class="sf-panel">
                        <h6 class="fw-bold mb-3">Komanda ({{ count($members) }})</h6>

                        <div class="d-flex flex-column gap-3">
                            @foreach ($members as $member)
                                <div class="border-bottom pb-3">
                                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                        <div class="min-w-0">
                                            <p class="mb-0 fw-semibold text-truncate">
                                                {{ $member['name'] ?: $member['email'] }}
                                                @if (! empty($member['is_owner']))
                                                    <span class="sf-badge sf-badge--success">Sahib</span>
                                                @endif
                                            </p>
                                            <p class="mb-0 text-muted small text-truncate">{{ $member['email'] }}</p>
                                        </div>

                                        @unless (! empty($member['is_owner']))
                                            <form action="{{ route('storefront.store.team.member.destroy', $member['user_id']) }}"
                                                  method="POST" onsubmit="return confirm('Üzv mağazadan çıxarılsın?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm sf-btn--danger">Çıxar</button>
                                            </form>
                                        @endunless
                                    </div>

                                    @unless (! empty($member['is_owner']))
                                        <form action="{{ route('storefront.store.team.member.update', $member['user_id']) }}"
                                              method="POST" class="d-flex gap-2 align-items-start mt-2 flex-wrap">
                                            @csrf
                                            @method('PUT')
                                            <select name="permissions[]" multiple size="{{ max(3, count($permissions)) }}"
                                                    class="form-select flex-grow-1" style="max-width: 420px;" required>
                                                @foreach ($permissions as $permission)
                                                    <option value="{{ $permission['key'] }}"
                                                            @selected(in_array($permission['key'], $member['permissions'] ?? [], true))>
                                                        {{ $permission['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm">Yadda saxla</button>
                                        </form>
                                    @endunless
                                </div>
                            @endforeach
                        </div>

                        @if (count($invitations) > 0)
                            <h6 class="fw-bold mt-4 mb-2">Aktiv dəvət linkləri</h6>
                            <div class="d-flex flex-column gap-2">
                                @foreach ($invitations as $invitation)
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <input type="text" value="{{ $invitation['url'] }}" readonly
                                               class="form-control form-control-sm" style="max-width: 380px;"
                                               data-sf-copy-source>
                                        <button type="button" class="sf-btn sf-btn--ghost sf-btn--sm"
                                                data-sf-copy="{{ $invitation['url'] }}">Kopyala</button>
                                        <span class="text-muted small">
                                            {{ $invitation['expires_at'] ? \Illuminate\Support\Carbon::parse($invitation['expires_at'])->format('d.m.Y') : '' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-storefront.layouts.app>
