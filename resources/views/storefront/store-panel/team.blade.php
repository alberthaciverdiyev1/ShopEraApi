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
    $initials = function (array $member): string {
        $name = trim((string) ($member['name'] ?? '')) ?: (string) ($member['email'] ?? '');
        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(mb_substr($parts[0] ?? 'U', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
    };
    $date = fn ($value): string => $value ? \Illuminate\Support\Carbon::parse($value)->format('d.m.Y') : '—';
    $membersCount = count($members) - 1; // the owner is the first row
    $owner = $members[0] ?? null;
@endphp

<x-storefront.layouts.app :title="'Komanda — '.$localized($vendor['name'] ?? '').' — ShopEra'">
    <div class="section-padding fix elan-page">
        <div class="container elan-page__narrow">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="mb-1">Komanda</h2>
                    <p class="text-muted mb-0 small">
                        {{ $localized($vendor['name'] ?? '') }} mağazasında sizdən başqa
                        {{ max(0, $membersCount) }} üzv var.
                    </p>
                </div>
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
                    <h6 class="fw-bold mb-2">Yeni dəvət linki hazırdır</h6>
                    <p class="text-muted small mb-2">
                        Bu linki əlavə etmək istədiyiniz şəxsə göndərin. Link 7 gün ərzində etibarlıdır;
                        şəxs daxil olub dəvəti qəbul edəndən sonra komanda siyahısında görünəcək.
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
                    <form action="{{ route('storefront.store.team.invite') }}" method="POST" class="sf-panel"
                          data-sf-team-invite>
                        @csrf
                        <h6 class="fw-bold mb-1">Üzv dəvət et</h6>
                        <p class="text-muted small mb-3">
                            Üzvün mağazada nələri idarə edə biləcəyini seçin, link yaradıb paylaşın.
                        </p>

                        @if (count($presets) > 0)
                            <span class="d-block mb-1 small fw-semibold">Hazır rollar</span>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach ($presets as $preset)
                                    <button type="button" class="sf-btn sf-btn--ghost sf-btn--sm"
                                            data-sf-preset="{{ implode(',', $preset['permissions']) }}"
                                            title="{{ implode(', ', array_map($permissionLabel, $preset['permissions'])) }}">
                                        {{ $preset['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <span class="d-block mb-1 small fw-semibold">İcazələr</span>
                        <div class="d-flex flex-column gap-2 mb-3">
                            @foreach ($permissions as $permission)
                                <label class="d-flex gap-2 align-items-start border rounded p-2">
                                    <input class="form-check-input mt-1" type="checkbox" name="permissions[]"
                                           value="{{ $permission['key'] }}"
                                           @checked(in_array($permission['key'], old('permissions', []), true))>
                                    <span class="min-w-0">
                                        <span class="d-block fw-semibold small">{{ $permission['label'] }}</span>
                                        <span class="d-block text-muted small">{{ $permission['description'] ?? '' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <button type="submit" class="theme-btn">Dəvət linki yarat</button>
                    </form>

                    <div class="sf-panel mt-4">
                        <h6 class="fw-bold mb-2">Necə işləyir?</h6>
                        <ol class="text-muted small ps-3 mb-0">
                            <li class="mb-1">İcazələri seçib dəvət linki yaradın.</li>
                            <li class="mb-1">Linki üzvə göndərin — 7 gün etibarlıdır.</li>
                            <li class="mb-1">Üzv daxil olub dəvəti qəbul edir və mağaza panelinə qoşulur.</li>
                            <li>İcazələri istənilən vaxt buradan dəyişə və ya üzvü çıxara bilərsiniz.</li>
                        </ol>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="sf-panel">
                        <h6 class="fw-bold mb-3">Komanda ({{ count($members) }})</h6>

                        <div class="d-flex flex-column gap-3">
                            @foreach ($members as $member)
                                <div class="border-bottom pb-3">
                                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                                  style="width: 40px; height: 40px;">
                                                @if (! empty($member['avatar']))
                                                    <img src="{{ $member['avatar'] }}" alt=""
                                                         class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                                @else
                                                    {{ $initials($member) }}
                                                @endif
                                            </span>
                                            <div class="min-w-0">
                                                <p class="mb-0 fw-semibold text-truncate">
                                                    {{ $member['name'] ?: $member['email'] }}
                                                    @if (! empty($member['is_owner']))
                                                        <span class="sf-badge sf-badge--success">Sahib</span>
                                                    @endif
                                                </p>
                                                <p class="mb-0 text-muted small text-truncate">
                                                    {{ $member['email'] }}
                                                    @if (! empty($member['joined_at']))
                                                        · Qoşulub: {{ $date($member['joined_at']) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        @unless (! empty($member['is_owner']))
                                            <form action="{{ route('storefront.store.team.member.destroy', $member['user_id']) }}"
                                                  method="POST" onsubmit="return confirm('{{ $member['name'] ?: $member['email'] }} mağazadan çıxarılsın?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm sf-btn--danger">Çıxar</button>
                                            </form>
                                        @endunless
                                    </div>

                                    @unless (! empty($member['is_owner']))
                                        <p class="mb-1 mt-2 small text-muted">İcazələr:</p>
                                        <div class="d-flex flex-wrap gap-1 mb-2">
                                            @forelse (($member['permissions'] ?? []) as $key)
                                                <span class="sf-badge">{{ $permissionLabel($key) }}</span>
                                            @empty
                                                <span class="text-muted small">İcazə verilməyib.</span>
                                            @endforelse
                                        </div>

                                        <details class="mt-1">
                                            <summary class="small text-muted" style="cursor: pointer;">İcazələri dəyiş</summary>
                                            <form action="{{ route('storefront.store.team.member.update', $member['user_id']) }}"
                                                  method="POST" class="mt-2">
                                                @csrf
                                                @method('PUT')
                                                <div class="d-flex flex-column gap-1 mb-2">
                                                    @foreach ($permissions as $permission)
                                                        <label class="d-flex gap-2 align-items-start small">
                                                            <input class="form-check-input mt-1" type="checkbox"
                                                                   name="permissions[]" value="{{ $permission['key'] }}"
                                                                   @checked(in_array($permission['key'], $member['permissions'] ?? [], true))>
                                                            <span>
                                                                <span class="fw-semibold">{{ $permission['label'] }}</span>
                                                                <span class="d-block text-muted">{{ $permission['description'] ?? '' }}</span>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm">Yadda saxla</button>
                                            </form>
                                        </details>
                                    @endunless
                                </div>
                            @endforeach
                        </div>

                        @if ($owner)
                            <p class="text-muted small mb-0 mt-3">
                                Sahib bütün icazələrə malikdir və komandanı yalnız o idarə edir.
                            </p>
                        @endif
                    </div>

                    <div class="sf-panel mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold mb-0">Aktiv dəvət linkləri</h6>
                            <span class="sf-badge">{{ count($invitations) }}</span>
                        </div>

                        @if (count($invitations) === 0)
                            <p class="text-muted small mb-0">
                                Hazırda aktiv dəvət linki yoxdur. Yuxarıdan yeni link yarada bilərsiniz.
                            </p>
                        @else
                            <div class="d-flex flex-column gap-3">
                                @foreach ($invitations as $invitation)
                                    <div class="border-bottom pb-2">
                                        <div class="d-flex gap-2 flex-wrap align-items-center">
                                            <input type="text" value="{{ $invitation['url'] }}" readonly
                                                   class="form-control form-control-sm" style="max-width: 380px;"
                                                   data-sf-copy-source>
                                            <button type="button" class="sf-btn sf-btn--ghost sf-btn--sm"
                                                    data-sf-copy="{{ $invitation['url'] }}">Kopyala</button>
                                            <form action="{{ route('storefront.store.team.invitation.destroy', $invitation['token']) }}"
                                                  method="POST" onsubmit="return confirm('Dəvət linki ləğv edilsin?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm sf-btn--danger">Ləğv et</button>
                                            </form>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1 mt-2 align-items-center">
                                            @foreach (($invitation['permissions'] ?? []) as $key)
                                                <span class="sf-badge">{{ $permissionLabel($key) }}</span>
                                            @endforeach
                                            <span class="text-muted small ms-auto">
                                                Bitir: {{ $date($invitation['expires_at'] ?? null) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Quick role presets tick the matching permission checkboxes.
            document.querySelectorAll('[data-sf-preset]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const form = button.closest('[data-sf-team-invite]');
                    if (!form) {
                        return;
                    }

                    const keys = (button.dataset.sfPreset || '').split(',').filter(Boolean);
                    form.querySelectorAll('input[name="permissions[]"]').forEach(function (input) {
                        input.checked = keys.indexOf(input.value) !== -1;
                    });
                });
            });
        });
    </script>
</x-storefront.layouts.app>
