@props(['popup' => null])

@if ($popup)
    @php
        $isVideo = ($popup['type'] ?? null) === 'video'
            || (empty($popup['image']) && ! empty($popup['video']));
    @endphp

    <div class="sf-popup" data-sf-popup hidden>
        <div class="sf-popup__overlay" data-sf-popup-close></div>

        <div class="sf-popup__card" role="dialog" aria-modal="true" aria-label="Pop-up">
            <button type="button" class="sf-popup__close" data-sf-popup-close aria-label="Bağla">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>

            @if ($isVideo && ! empty($popup['video']))
                <video src="{{ $popup['video'] }}" autoplay muted loop playsinline controls></video>
            @elseif (! empty($popup['image']))
                <img src="{{ $popup['image'] }}" alt="ShopEra">
            @endif
        </div>
    </div>

@endif
