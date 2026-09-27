{{-- Elan kartı: tətbiqdəki dizaynın eynisi — 4:3 şəkil, qiymət, başlıq,
     bölmənin sahələri, şəhər və vaxt. Saytın öz rəng dəyişənləri işlənir. --}}
<style>
    .lst-ink { --lst-ink: #002255; --lst-muted: #667085; --lst-line: #edeff3;
               --lst-soft: #f2f4f7; --lst-brand: #ff6300; --lst-call: #12a150; }
    .lst-grid { display: grid; gap: 16px 12px; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); }
    .lst-card {
        display: block; background: #fff; border: 1px solid var(--lst-line);
        border-radius: 12px; overflow: hidden; text-decoration: none;
        box-shadow: 0 1px 2px rgba(0,34,85,.06); transition: box-shadow .15s ease, transform .15s ease;
    }
    .lst-card:hover { box-shadow: 0 8px 24px rgba(0,34,85,.1); transform: translateY(-2px); }
    .lst-cover { position: relative; aspect-ratio: 4 / 3; background: var(--lst-soft); }
    .lst-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .lst-cover .lst-empty {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        color: #98a2b3; font-size: 12px;
    }
    .lst-vip {
        position: absolute; left: 8px; top: 8px; height: 20px; padding: 0 7px;
        display: inline-flex; align-items: center; gap: 4px; border-radius: 6px;
        background: var(--lst-brand); color: #fff; font-size: 10px; font-weight: 700;
    }
    .lst-body { padding: 10px 10px 12px; }
    .lst-price { margin: 0; font-size: 15px; font-weight: 700; color: var(--lst-ink); line-height: 20px; }
    .lst-title {
        margin: 3px 0 0; font-size: 13px; font-weight: 500; color: var(--lst-ink); line-height: 18px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .lst-fields, .lst-meta {
        margin: 3px 0 0; color: var(--lst-muted);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .lst-fields { font-size: 12px; line-height: 16px; }
    .lst-meta { font-size: 11px; line-height: 15px; white-space: normal; }

    .lst-chip {
        display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 10px;
        border-radius: 8px; background: var(--lst-soft); color: var(--lst-ink);
        font-size: 13px; font-weight: 500;
    }
    .lst-chip.green { background: #e4f5ec; color: #12a150; }
    .lst-chip.blue { background: #e6effd; color: #2f6fed; }

    .lst-block {
        background: #fff; border: 1px solid var(--lst-line); border-radius: 16px;
        padding: 18px; margin-bottom: 14px;
    }
    .lst-block h2 { margin: 0 0 12px; font-size: 16px; font-weight: 700; color: var(--lst-ink); }
    .lst-spec { display: flex; gap: 16px; padding: 11px 0; border-bottom: 1px solid var(--lst-soft); }
    .lst-spec:last-child { border-bottom: 0; }
    .lst-spec dt { flex: 4; margin: 0; font-size: 13px; color: var(--lst-muted); }
    .lst-spec dd { flex: 6; margin: 0; font-size: 13px; font-weight: 500; color: var(--lst-ink); text-align: right; }
    .lst-spec dd .lst-tag {
        display: inline-block; margin: 0 0 4px 4px; padding: 4px 10px; border-radius: 8px;
        background: var(--lst-soft); font-size: 12px;
    }

    .lst-hero-price { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -.6px; color: var(--lst-ink); }
    .lst-hero-title { margin: 6px 0 0; font-size: 18px; font-weight: 600; color: var(--lst-ink); }
    .lst-hero-sum { margin: 6px 0 0; font-size: 14px; color: var(--lst-muted); }

    .lst-call {
        display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        height: 52px; padding: 0 26px; border-radius: 14px; background: var(--lst-call);
        color: #fff; font-size: 16px; font-weight: 700; text-decoration: none;
    }
    .lst-call:hover { background: #0e8843; }
    .lst-warn {
        display: flex; gap: 10px; padding: 12px; border-radius: 12px;
        background: #fff4ea; border: 1px solid #ffd9b8; color: #7a3a00;
        font-size: 13px; line-height: 1.45;
    }
    .lst-gallery { display: grid; gap: 8px; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); }
    .lst-gallery button {
        display: block; padding: 0; border: 2px solid transparent; border-radius: 10px;
        overflow: hidden; aspect-ratio: 4 / 3; background: var(--lst-soft); cursor: pointer;
    }
    .lst-gallery button.is-active { border-color: var(--lst-brand); }
    .lst-gallery img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .lst-main {
        position: relative; aspect-ratio: 4 / 3; border-radius: 16px; overflow: hidden;
        background: var(--lst-soft); cursor: zoom-in;
    }
    .lst-main img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .lst-main .lst-count {
        position: absolute; left: 50%; bottom: 12px; transform: translateX(-50%);
        height: 24px; padding: 0 10px; display: inline-flex; align-items: center;
        border-radius: 12px; background: rgba(0, 34, 85, .62); color: #fff;
        font-size: 12px; font-weight: 500;
    }
    .lst-nav {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 38px; height: 38px; border: 0; border-radius: 50%; cursor: pointer;
        background: rgba(0, 34, 85, .55); color: #fff; font-size: 20px; line-height: 1;
        display: flex; align-items: center; justify-content: center;
    }
    .lst-nav.prev { left: 10px; }
    .lst-nav.next { right: 10px; }

    /* Böyük baxış: şəkil səhifənin üstündə açılır, linkə atmır. */
    .lst-lightbox {
        position: fixed; inset: 0; z-index: 200; display: none;
        align-items: center; justify-content: center; background: rgba(0, 0, 0, .92);
    }
    .lst-lightbox.is-open { display: flex; }
    .lst-lightbox img { max-width: 94vw; max-height: 86vh; object-fit: contain; border-radius: 8px; }
    .lst-lightbox .lst-close {
        position: absolute; top: 16px; right: 16px; width: 42px; height: 42px;
        border: 0; border-radius: 50%; background: rgba(255, 255, 255, .18);
        color: #fff; font-size: 22px; cursor: pointer;
    }
    .lst-lightbox .lst-nav { background: rgba(255, 255, 255, .18); width: 46px; height: 46px; }
    .lst-lightbox .lst-count {
        position: absolute; left: 50%; bottom: 20px; transform: translateX(-50%);
        color: #fff; font-size: 13px; opacity: .85;
    }
    .lst-video { position: relative; aspect-ratio: 16 / 9; border-radius: 12px; overflow: hidden; background: #000; }
    .lst-video iframe { width: 100%; height: 100%; border: 0; display: block; }
    .lst-map { height: 260px; border-radius: 12px; overflow: hidden; }

    .lst-filters { display: grid; gap: 10px; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
    .lst-filters label { display: block; font-size: 12px; font-weight: 500; color: var(--lst-muted); margin-bottom: 5px; }
    .lst-filters input, .lst-filters select {
        width: 100%; height: 42px; padding: 0 12px; border-radius: 10px;
        border: 1px solid #e8ebf0; background: #fff; font: inherit; font-size: 14px; color: var(--lst-ink);
    }
    .lst-range { display: flex; gap: 8px; }
    .lst-sections { display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }

    /* Elanın səhifəsi: geniş ekranda iki sütun, telefonda bir. */
    .lst-page { display: grid; gap: 18px; align-items: start; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); }

    /* Telefonda süzgəc yığılır: açılan kimi elanlar ekranın altına düşürdü. */
    .lst-filter > summary {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        cursor: pointer; list-style: none; font-weight: 700; color: var(--lst-ink);
    }
    .lst-filter > summary::-webkit-details-marker { display: none; }
    .lst-filter > summary::after {
        content: ''; width: 9px; height: 9px; flex: none; margin-right: 4px;
        border-right: 2px solid var(--lst-muted); border-bottom: 2px solid var(--lst-muted);
        transform: rotate(45deg) translate(-2px, -2px); transition: transform .15s ease;
    }
    .lst-filter[open] > summary::after { transform: rotate(-135deg) translate(-3px, -3px); }
    .lst-filter > summary + * { margin-top: 14px; }
    .lst-section-card {
        display: block; padding: 18px; border-radius: 16px; text-decoration: none;
        background: linear-gradient(135deg, #1e3a5f, #2e6fa7); color: #fff; min-height: 96px;
        position: relative; overflow: hidden;
    }
    .lst-section-card.property { background: linear-gradient(135deg, #3a3f52, #6b7189); }
    .lst-section-card img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .55; }
    .lst-section-card span { position: relative; font-size: 17px; font-weight: 700; }
    .lst-section-card small { position: relative; display: block; margin-top: 4px; opacity: .85; font-size: 13px; }

    @media (max-width: 860px) {
        .lst-page { grid-template-columns: minmax(0, 1fr); }
    }

    @media (max-width: 640px) {
        /* Telefonda iki sütun qalır — tətbiqdəki kimi. */
        .lst-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 10px; }
        .lst-block { padding: 14px; border-radius: 14px; }
        .lst-filters { grid-template-columns: minmax(0, 1fr); gap: 12px; }
        .lst-hero-price { font-size: 24px; }
        .lst-hero-title { font-size: 16px; }
        .lst-call { width: 100%; }
        .lst-map { height: 220px; }
        .lst-gallery { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .lst-spec { gap: 10px; }
        .lst-spec dt, .lst-spec dd { font-size: 12.5px; }
    }

    /* Geniş ekranda süzgəc həmişə açıqdır, yığılma düyməsi görünmür. */
    @media (min-width: 861px) {
        .lst-filter > summary { display: none; }
        .lst-filter > summary + * { margin-top: 0; }
    }
</style>
