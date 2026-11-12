<x-storefront.layouts.app :title="'Mağaza aç — ShopEra'">
    <div class="section-padding fix elan-page">
        <div class="container elan-page__narrow-sm">
            <h2 class="mb-1">Mağaza aç</h2>
            <p class="text-muted mb-4">Öz mağazanızı yaradın və elanlarınızı bir brend altında idarə edin.</p>

            @if ($errors->any())
                <div class="sf-alert sf-alert--error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('storefront.store.open.create') }}" method="POST" enctype="multipart/form-data" class="sf-panel">
                @csrf
                <div class="sf-account__form">
                    <div class="sf-form-row">
                        <label>
                            <span>Mağaza adı *</span>
                            <input type="text" name="name" value="{{ old('name') }}" required maxlength="120">
                        </label>
                        <label>
                            <span>Telefon</span>
                            <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}" placeholder="+994...">
                        </label>
                    </div>

                    <label>
                        <span>Təsvir</span>
                        <textarea name="description" rows="4">{{ old('description') }}</textarea>
                    </label>

                    <label>
                        <span>Ünvan</span>
                        <input type="text" name="address" value="{{ old('address') }}">
                    </label>

                    <label>
                        <span>Loqo</span>
                        <input type="file" name="logo" accept="image/*">
                    </label>

                    <button type="submit" class="theme-btn">Mağaza yarat</button>
                </div>
            </form>
        </div>
    </div>
</x-storefront.layouts.app>
