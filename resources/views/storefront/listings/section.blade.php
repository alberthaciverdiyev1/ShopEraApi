@extends('storefront.layout')
@section('content')
@include('storefront.listings.partials.style')

<section class="page-hero lst-ink">
    <div class="shell">
        <h1>{{ $section->name }}</h1>
        <p>{{ $ads->total() }} elan</p>

        <div class="cat-tabs">
            @foreach($sections as $item)
                <a class="cat-tab {{ $item->id === $section->id ? 'active' : '' }}"
                   href="{{ route('storefront.listings.section', ['section' => $item->key]) }}">{{ $item->name }}</a>
            @endforeach
        </div>
    </div>
</section>

<section class="section lst-ink" style="padding-top:4px;border:0">
    <div class="shell">
        {{-- Filtrlər bölmənin öz sahələrindən qurulur: burada heç bir sahə
             adı yazılmayıb, admin yeni sahə əlavə edəndə filtr də gəlir. --}}
        {{-- Telefonda süzgəc yığılır: 16 sahə ekranı doldurub elanları aşağı
             atırdı. Geniş ekranda `open` və CSS onu həmişə açıq saxlayır. --}}
        <form class="lst-block" method="get">
          <details class="lst-filter" {{ request()->hasAny(['search', 'price_from', 'price_to', 'f']) ? 'open' : '' }}>
            <summary>Filtr{{ count((array) request('f', [])) ? ' ('.count(array_filter((array) request('f', []))).')' : '' }}</summary>
            <div class="lst-filters">
                <div>
                    <label for="q">Axtarış</label>
                    <input id="q" name="search" value="{{ request('search') }}" placeholder="Açar söz">
                </div>

                <div>
                    <label>Qiymət</label>
                    <div class="lst-range">
                        <input name="price_from" value="{{ request('price_from') }}" placeholder="Min" inputmode="numeric">
                        <input name="price_to" value="{{ request('price_to') }}" placeholder="Maks" inputmode="numeric">
                    </div>
                </div>

                @foreach($filters as $filter)
                    @php($field = $filter['field'])
                    @php($value = $chosen[$field->key] ?? null)
                    <div>
                        <label>{{ $field->label }}{{ $field->unit ? ", {$field->unit}" : '' }}</label>

                        @if($field->usesOptions() && $filter['options']->isNotEmpty())
                            <select name="f[{{ $field->key }}]">
                                <option value="">Fərq etməz</option>
                                @foreach($filter['options'] as $option)
                                    <option value="{{ $option->value }}" @selected(is_string($value) && $value === $option->value)>
                                        {{ $option->label }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif($field->type === 'boolean')
                            <select name="f[{{ $field->key }}]">
                                <option value="">Fərq etməz</option>
                                <option value="1" @selected($value === '1')>Bəli</option>
                                <option value="0" @selected($value === '0')>Xeyr</option>
                            </select>
                        @elseif($field->is_range)
                            <div class="lst-range">
                                <input name="f[{{ $field->key }}][from]" value="{{ is_array($value) ? ($value['from'] ?? '') : '' }}" placeholder="Min" inputmode="numeric">
                                <input name="f[{{ $field->key }}][to]" value="{{ is_array($value) ? ($value['to'] ?? '') : '' }}" placeholder="Maks" inputmode="numeric">
                            </div>
                        @else
                            <input name="f[{{ $field->key }}]" value="{{ is_string($value) ? $value : '' }}">
                        @endif
                    </div>
                @endforeach
            </div>

            <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
                <button class="pill-button" type="submit">Elanları göstər</button>
                <a class="ghost-button" href="{{ route('storefront.listings.section', ['section' => $section->key]) }}">Təmizlə</a>
            </div>
          </details>
        </form>

        @if($cards->isEmpty())
            <div class="info-card">
                <h2>Nəticə tapılmadı</h2>
                <p>Başqa şərtlərlə yenidən yoxlayın.</p>
            </div>
        @else
            <div class="lst-grid">
                @foreach($cards as $card)
                    @include('storefront.listings.partials.card', ['card' => $card])
                @endforeach
            </div>
            <div class="pagination">{{ $ads->links('vendor.pagination.storefront') }}</div>
        @endif
    </div>
</section>
<script>
    // Geniş ekranda süzgəc açıq durur, telefonda yığılır. CSS <details>-i
    // aça bilmir, ona görə bu bir neçə sətir lazımdır.
    (function () {
        var box = document.querySelector('.lst-filter');
        if (!box) return;

        var wide = window.matchMedia('(min-width: 861px)');
        var sync = function () { if (wide.matches) box.open = true; };

        sync();
        wide.addEventListener('change', sync);
    })();
</script>
@endsection
