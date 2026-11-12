@props(['user'])

@php
    $initials = collect([$user?->name, $user?->surname])
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('') ?: 'U';
@endphp

<div class="sf-account">
    <div class="sf-account__avatar-row">
        <div class="sf-account__avatar">
            @if ($user?->avatar)
                <img src="{{ $user->avatar }}" alt="{{ $user->name ?? 'Profil' }}">
            @else
                <span>{{ $initials }}</span>
            @endif
        </div>

        <div class="sf-account__avatar-actions">
            <h6>Profil şəkli</h6>
            <p>JPG, PNG və ya WEBP. Maksimum 5MB.</p>

            <div class="sf-account__avatar-buttons">
                <form action="{{ route('storefront.settings.avatar') }}" method="POST" enctype="multipart/form-data" id="sf-avatar-form">
                    @csrf
                    <label class="sf-btn sf-btn--ghost sf-btn--sm" for="sf-avatar-input">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Şəkil yüklə</span>
                    </label>
                    <input id="sf-avatar-input" type="file" name="avatar"
                           accept="image/jpeg,image/png,image/jpg,image/webp" class="d-none"
                           onchange="this.form.submit()">
                </form>

                @if ($user?->avatar)
                    <form action="{{ route('storefront.settings.avatar.remove') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm">
                            <i class="fa-solid fa-trash-can"></i> Sil
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <form action="{{ route('storefront.settings.profile') }}" method="POST" class="sf-account__form">
        @csrf
        @method('PUT')

        <div class="sf-form-row">
            <label>
                <span>Ad *</span>
                <input type="text" name="name" value="{{ old('name', $user?->name) }}" required maxlength="255">
            </label>
            <label>
                <span>Soyad</span>
                <input type="text" name="surname" value="{{ old('surname', $user?->surname) }}" maxlength="255">
            </label>
        </div>

        <div class="sf-form-row">
            <label>
                <span>E-poçt</span>
                <input type="email" name="email" value="{{ old('email', $user?->email) }}" maxlength="255">
            </label>
            <label>
                <span>Telefon</span>
                <input type="text" value="{{ $user?->phone }}" disabled>
                <small>Telefonu dəyişmək üçün təsdiq kodu tələb olunur.</small>
            </label>
        </div>

        <button type="submit" class="sf-btn">Dəyişiklikləri yadda saxla</button>
    </form>
</div>
