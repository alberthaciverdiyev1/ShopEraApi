@php
    $user = auth()->user();
    $initials = collect([$user?->name, $user?->surname])
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('') ?: 'U';

    $tabs = [
        'dashboard' => ['label' => 'Hesab paneli', 'icon' => 'fa-solid fa-grid-2'],
        'messages' => ['label' => 'Mesajlar', 'icon' => 'fa-regular fa-comment-dots'],
        'addresses' => ['label' => 'Ünvanlar', 'icon' => 'fa-solid fa-location-dot'],
        'settings' => ['label' => 'Parametrlər', 'icon' => 'fa-regular fa-gear'],
        'logout' => ['label' => 'Çıxış', 'icon' => 'fa-solid fa-sign-out-alt'],
    ];
@endphp

<x-storefront.layouts.app :title="'Hesabım — ShopEra'">
    <x-storefront.breadcrumb title="Hesabım" />

    <div class="dashboard-section section-padding fix account-dashboard">
        <div class="container">
            <div class="row g-4 account-dashboard-grid">
                <div class="col-xl-3">
                    <div class="dashboard-navigation-sidebar">
                        <h3>Naviqasiya</h3>
                        <div class="nav flex-column nav-pills" role="tablist" aria-orientation="vertical">
                            @foreach ($tabs as $key => $item)
                                <button class="nav-link {{ $tab === $key ? 'active' : '' }}"
                                        type="button" role="tab"
                                        aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                                        data-sf-dash-tab="{{ $key }}">
                                    <i class="{{ $item['icon'] }}"></i>{{ $item['label'] }}
                                </button>
                            @endforeach

                            <a class="nav-link" href="/wishlist">
                                <i class="fa-light fa-heart"></i>İstək siyahısı
                            </a>
                            <a class="nav-link" href="/elanlarim">
                                <i class="fa-solid fa-rectangle-list"></i>Elanlarım
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-9">
                    @if (session('status'))
                        <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="sf-alert sf-alert--error">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="sf-alert sf-alert--error">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="sf-dash-panes">
                        {{-- Overview --}}
                        <section class="sf-dash-pane {{ $tab === 'dashboard' ? 'is-active' : '' }}" data-sf-dash-pane="dashboard">
                            <div class="dashboard-wrapper">
                                <div class="dashboard-top">
                                    <div class="row g-4 align-items-stretch">
                                        <div class="col-md-6 col-12">
                                            <div class="dashboard-profile h-100 d-flex flex-column justify-content-center align-items-center">
                                                <div class="thumb avatar-thumb position-relative">
                                                    @if ($user?->avatar)
                                                        <img src="{{ $user->avatar }}" alt="Profil şəkli" class="avatar-img">
                                                    @else
                                                        <div class="sf-dash-avatar-placeholder">{{ $initials }}</div>
                                                    @endif
                                                    <label class="avatar-camera-btn" title="Şəkli dəyiş" aria-label="Şəkli dəyiş">
                                                        <i class="fa-solid fa-camera"></i>
                                                        <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp" class="d-none"
                                                               onchange="document.getElementById('sf-avatar-input').files = this.files; document.getElementById('sf-avatar-form').submit();">
                                                    </label>
                                                </div>
                                                <h3>{{ $user?->name ?? 'Hesabım' }}</h3>
                                                <p>{{ $user?->email ?? $user?->phone ?? 'Müştəri' }}</p>
                                                <button type="button" class="sf-link-button" data-sf-dash-tab="settings">Profili redaktə et</button>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-12">
                                            <div class="dashboard-profile-info h-100 d-flex flex-column justify-content-center">
                                                <h6>Default ünvan</h6>
                                                @if ($defaultAddress)
                                                    <h5>{{ $defaultAddress['full_name'] ?? '' }}</h5>
                                                    <button type="button" class="address sf-link-button text-start" data-sf-dash-tab="addresses">
                                                        {{ $defaultAddress['street_building_number'] ?? '' }}@if (! empty($defaultAddress['unit_floor_apartment'])), {{ $defaultAddress['unit_floor_apartment'] }}@endif,
                                                        {{ $defaultAddress['town_village_district'] ?? '' }}, {{ $defaultAddress['city'] ?? '' }}
                                                    </button>
                                                    @if (! empty($defaultAddress['contact_number']))
                                                        <a href="tel:{{ $defaultAddress['contact_number'] }}" class="phone">{{ $defaultAddress['contact_number'] }}</a>
                                                    @endif
                                                    <button type="button" class="edit sf-link-button text-start" data-sf-dash-tab="addresses">Ünvanları idarə et</button>
                                                @else
                                                    <p class="text-muted mt-2 mb-3">Hələ ünvan əlavə edilməyib.</p>
                                                    <button type="button" class="edit sf-link-button text-start" data-sf-dash-tab="addresses">+ Ünvan əlavə et</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="order-history">
                                    <div class="header">
                                        <h2>Son sifarişlər</h2>
                                    </div>
                                    @if (count($recentOrders))
                                        <div class="sf-table-wrap">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Sifariş</th>
                                                        <th>Tarix</th>
                                                        <th>Məbləğ</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($recentOrders as $order)
                                                        <tr>
                                                            <td>#{{ $order['id'] }}</td>
                                                            <td>{{ \Illuminate\Support\Carbon::parse($order['created_at'])->translatedFormat('d M, Y') }}</td>
                                                            <td>{{ number_format((float) $order['total_price'], 2) }} ₼</td>
                                                            <td>
                                                                <span class="status">{{ $order['latest_status']['status'] ?? 'Yeni' }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">Hələ sifarişiniz yoxdur.</p>
                                    @endif
                                </div>
                            </div>
                        </section>

                        {{-- Messages: chat with support --}}
                        <section class="sf-dash-pane {{ $tab === 'messages' ? 'is-active' : '' }}" data-sf-dash-pane="messages">
                            <div class="sf-panel">
                                <h2 class="sf-panel__title">Dəstək ilə yazışma</h2>

                                <div class="sf-support-chat">
                                    <div class="sf-support-chat__log" data-sf-support-chat-log>
                                        @forelse ($chatMessages as $message)
                                            @php $mine = $message['sender_type'] === 'user'; @endphp
                                            <div class="sf-support-chat__row {{ $mine ? 'is-mine' : '' }}">
                                                <div class="sf-support-chat__bubble">
                                                    @if (! empty($message['message']))
                                                        <p class="sf-support-chat__text">{{ $message['message'] }}</p>
                                                    @endif
                                                    @foreach ($message['attachments'] ?? [] as $attachment)
                                                        <a href="{{ $attachment['url'] }}" target="_blank" rel="noopener">
                                                            <img src="{{ $attachment['url'] }}" alt="" loading="lazy">
                                                        </a>
                                                    @endforeach
                                                    <div class="sf-support-chat__meta">
                                                        <time>{{ \Illuminate\Support\Carbon::parse($message['created_at'])->format('d.m H:i') }}</time>
                                                        @if ($mine)
                                                            <form action="{{ route('storefront.dashboard.messages.destroy', $message['id']) }}" method="POST"
                                                                  onsubmit="return confirm('Mesaj silinsin?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="sf-support-chat__delete" aria-label="Mesajı sil">
                                                                    <i class="fa-regular fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-muted mb-0">Hələ mesaj yoxdur. Dəstək komandasına ilk mesajı yazın.</p>
                                        @endforelse
                                    </div>

                                    <form action="{{ route('storefront.dashboard.messages.send') }}" method="POST" enctype="multipart/form-data" class="sf-support-chat__form">
                                        @csrf
                                        <textarea name="message" rows="2" maxlength="2000" placeholder="Mesajınızı yazın…"></textarea>
                                        <div class="sf-support-chat__actions">
                                            <label class="sf-support-chat__attach">
                                                <input type="file" name="image" accept="image/*">
                                                <i class="fa-regular fa-image"></i>
                                                <span>Şəkil</span>
                                            </label>
                                            <button type="submit" class="theme-btn">Göndər</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </section>

                        {{-- Addresses --}}
                        <section class="sf-dash-pane {{ $tab === 'addresses' ? 'is-active' : '' }}" data-sf-dash-pane="addresses">
                            <div class="sf-panel">
                                <h2 class="sf-panel__title">Çatdırılma ünvanları</h2>

                                @if (count($addresses))
                                    <div class="sf-address-list">
                                        @foreach ($addresses as $address)
                                            <article class="sf-address-card {{ ! empty($address['is_default']) ? 'is-default' : '' }}">
                                                <div class="sf-address-card__body">
                                                    <h6>
                                                        {{ $address['full_name'] ?? '' }}
                                                        @if (! empty($address['is_default']))
                                                            <span class="sf-address-card__badge">Default</span>
                                                        @endif
                                                    </h6>
                                                    <p>{{ $address['street_building_number'] ?? '' }}@if (! empty($address['unit_floor_apartment'])), {{ $address['unit_floor_apartment'] }}@endif, {{ $address['town_village_district'] ?? '' }}, {{ $address['city'] ?? '' }}</p>
                                                    @if (! empty($address['contact_number']))
                                                        <a href="tel:{{ $address['contact_number'] }}">{{ $address['contact_number'] }}</a>
                                                    @endif
                                                </div>
                                                <div class="sf-address-card__actions">
                                                    @if (empty($address['is_default']))
                                                        <form action="{{ route('storefront.addresses.update', $address['id']) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="is_default" value="1">
                                                            <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm">Default et</button>
                                                        </form>
                                                    @endif
                                                    <form action="{{ route('storefront.addresses.destroy', $address['id']) }}" method="POST"
                                                          onsubmit="return confirm('Ünvan silinsin?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm sf-btn--danger">Sil</button>
                                                    </form>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                @endif

                                <form action="{{ route('storefront.addresses.store') }}" method="POST" class="sf-address-form">
                                    @csrf
                                    <h5>Yeni ünvan</h5>

                                    <div class="sf-form-row">
                                        <label>
                                            <span>Ad və soyad *</span>
                                            <input type="text" name="full_name" value="{{ old('full_name') }}" required maxlength="255">
                                        </label>
                                        <label>
                                            <span>Əlaqə nömrəsi *</span>
                                            <input type="text" name="contact_number" value="{{ old('contact_number') }}" required>
                                        </label>
                                    </div>

                                    <div class="sf-form-row">
                                        <label>
                                            <span>Şəhər *</span>
                                            <select name="city" required>
                                                @foreach ($cities as $city)
                                                    <option value="{{ $city['key'] ?? $city['name'] }}" @selected(old('city') === ($city['key'] ?? $city['name']))>
                                                        {{ $city['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label>
                                            <span>Rayon / qəsəbə *</span>
                                            <input type="text" name="town_village_district" value="{{ old('town_village_district') }}" required maxlength="255">
                                        </label>
                                    </div>

                                    <div class="sf-form-row">
                                        <label>
                                            <span>Küçə və bina *</span>
                                            <input type="text" name="street_building_number" value="{{ old('street_building_number') }}" required maxlength="255">
                                        </label>
                                        <label>
                                            <span>Mənzil / mərtəbə *</span>
                                            <input type="text" name="unit_floor_apartment" value="{{ old('unit_floor_apartment') }}" required maxlength="255">
                                        </label>
                                    </div>

                                    <label class="sf-checkbox">
                                        <input type="checkbox" name="is_default" value="1">
                                        <span>Default ünvan kimi istifadə et</span>
                                    </label>

                                    <button type="submit" class="sf-btn">Ünvanı yadda saxla</button>
                                </form>
                            </div>
                        </section>

                        {{-- Settings --}}
                        <section class="sf-dash-pane {{ $tab === 'settings' ? 'is-active' : '' }}" data-sf-dash-pane="settings">
                            <div class="sf-panel">
                                <h2 class="sf-panel__title">Hesab parametrləri</h2>
                                @include('storefront.settings._form', ['user' => $user])
                            </div>
                        </section>

                        {{-- Logout --}}
                        <section class="sf-dash-pane {{ $tab === 'logout' ? 'is-active' : '' }}" data-sf-dash-pane="logout">
                            <div class="sf-panel sf-panel--center">
                                <i class="fa-solid fa-arrow-right-from-bracket sf-panel__icon"></i>
                                <h2 class="sf-panel__title">Çıxış</h2>
                                <p>Hesabınızdan çıxmaq istədiyinizə əminsiniz?</p>
                                <form action="{{ route('storefront.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="sf-btn">Çıxışı təsdiqlə</button>
                                </form>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-storefront.layouts.app>
