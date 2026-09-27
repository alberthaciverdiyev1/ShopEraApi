<a class="lst-card" href="{{ $card['url'] }}">
    <div class="lst-cover">
        @if($card['cover'])
            <img src="{{ $card['cover'] }}" alt="{{ $card['title'] }}" loading="lazy">
        @else
            <div class="lst-empty">Şəkil yoxdur</div>
        @endif
        @if(in_array('vip', $card['badges'], true))<span class="lst-vip">VIP</span>@endif
    </div>
    <div class="lst-body">
        <p class="lst-price">{{ $card['price'] ?? 'Razılaşma yolu ilə' }}</p>
        <p class="lst-title">{{ $card['title'] }}</p>
        <p class="lst-fields">{{ implode(', ', $card['summary']) }}</p>
        <p class="lst-meta">{{ collect([$card['city'], $card['time']])->filter()->implode(', ') }}</p>
    </div>
</a>
