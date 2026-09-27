<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta['title'] ?? 'Teymur Store' }}</title>
    <meta name="description" content="{{ $meta['description'] ?? 'Teymur Store məhsullarına baxın və mobil tətbiqi yükləyin.' }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $meta['canonical'] ?? url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Teymur Store">
    <meta property="og:title" content="{{ $meta['title'] ?? 'Teymur Store' }}">
    <meta property="og:description" content="{{ $meta['description'] ?? 'Teymur Store məhsullarına baxın və mobil tətbiqi yükləyin.' }}">
    <meta property="og:image" content="{{ $meta['image'] ?? asset('notification_icon.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#f26a2e">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700;9..40,800;9..40,900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1a1826; --ink-soft: #3d3b4a; --muted: #8b8a95;
            --line: #e9e9f0; --bg: #fafafc; --soft: #f3f3f7;
            --brand: #f26a2e; --brand-light: #fff4ef; --brand-dark: #d94e1f;
            --gold: #f7bd35; --teal: #2fbcad; --indigo: #5b59c2;
            --coral: #ef6b6e; --amber: #eda946;
            --r: 16px; --r-sm: 10px; --pill: 999px;
            --shadow-sm: 0 2px 8px rgba(26,24,38,.05);
            --shadow: 0 8px 28px rgba(26,24,38,.08);
            --shadow-lg: 0 16px 44px rgba(26,24,38,.12);
            --font: 'DM Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; }
        html, body { max-width: 100%; overflow-x: hidden; }
        body { font-family: var(--font); color: var(--ink); background: #fff; -webkit-font-smoothing: antialiased; }
        body.modal-open { overflow: hidden; }
        a { color: inherit; text-decoration: none; }
        img, video { display: block; max-width: 100%; }
        button { font: inherit; cursor: pointer; }

        .shell { width: min(1200px, calc(100% - 40px)); margin: 0 auto; }

        .topbar {
            position: sticky; top: 0; z-index: 50;
            background: rgba(255,255,255,.9);
            backdrop-filter: blur(20px) saturate(1.4);
            -webkit-backdrop-filter: blur(20px) saturate(1.4);
            border-bottom: 1px solid rgba(233,233,240,.7);
        }
        .nav { display: flex; align-items: center; gap: 24px; height: 68px; position: relative; }
        .brand { display: flex; align-items: center; }
        .brand-logo { width: 160px; height: auto; max-height: 38px; object-fit: contain; }
        .nav-links { display: flex; align-items: center; gap: 4px; margin-left: auto; }
        .nav-links a {
            padding: 7px 14px; border-radius: var(--r-sm);
            color: var(--ink-soft); font-weight: 600; font-size: 15px;
            transition: color .15s, background .15s;
        }
        .nav-links a:hover { color: var(--brand); background: var(--brand-light); }
        .nav-links .pill-button,
        .mobile-nav .pill-button { color: #fff; }
        .mobile-menu-btn {
            display: none; background: none; border: 0; padding: 6px;
            margin-left: auto; color: var(--ink);
        }
        .mobile-menu-btn svg { width: 26px; height: 26px; }
        .mobile-nav {
            display: none; position: absolute; top: 100%; left: -20px; right: -20px;
            background: #fff; border-bottom: 1px solid var(--line);
            padding: 10px 20px 14px; flex-direction: column; gap: 2px;
            box-shadow: var(--shadow); z-index: 100;
        }
        .mobile-nav.open { display: flex; }
        .mobile-nav a {
            padding: 11px 0; font-weight: 600; font-size: 15px;
            color: var(--ink-soft); border-bottom: 1px solid var(--line);
        }
        .mobile-nav a:last-child { border: 0; }

        .pill-button {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            height: 42px; padding: 0 20px; border-radius: var(--pill);
            color: #fff; background: var(--brand); font-weight: 700; font-size: 14px;
            border: 0; box-shadow: 0 4px 14px rgba(242,106,46,.28);
            transition: background .15s, transform .1s;
        }
        .pill-button:hover { background: var(--brand-dark); transform: translateY(-1px); }
        .ghost-button {
            display: inline-flex; align-items: center; justify-content: center;
            height: 38px; padding: 0 16px; border-radius: var(--pill);
            border: 1.5px solid var(--line); background: #fff;
            color: var(--ink-soft); font-weight: 600; font-size: 14px;
            white-space: nowrap; transition: border-color .15s;
        }
        .ghost-button:hover { border-color: #ccc; }

        .hero-band {
            background: linear-gradient(160deg, #d0e8fa 0%, #e6f1fb 40%, #fdf3ed 80%, #fff 100%);
            padding: 48px 0 52px; overflow: hidden;
        }
        .hero-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 44px; align-items: center; }
        .hero-grid > *, .download-panel > *, .footer-grid > *, .contact-grid > *, .product-detail > * { min-width: 0; }
        .hero-copy h1 { font-size: clamp(36px, 5.5vw, 68px); font-weight: 900; line-height: .94; letter-spacing: -.03em; }
        .hero-copy p { max-width: 440px; margin: 16px 0 26px; color: var(--muted); font-size: 16px; line-height: 1.6; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 10px; }

        .section { padding: 28px 0; }
        .section + .section { border-top: 1px solid var(--line); }
        .section-head {
            display: flex; align-items: flex-end;
            justify-content: space-between; gap: 16px; margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .section-head h2 { font-size: clamp(20px, 2.8vw, 28px); font-weight: 800; letter-spacing: -.02em; }
        .section-head p { margin: 3px 0 0; color: var(--muted); font-size: 14px; }

        .product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .product-grid > *,
        .category-grid > * { min-width: 0; }
        .product-card {
            border-radius: var(--r); overflow: hidden;
            background: #fff; border: 1px solid var(--line);
            transition: transform .2s, box-shadow .2s;
        }
        .product-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .product-media {
            position: relative; aspect-ratio: 1; overflow: hidden;
            background: var(--soft); display: block;
        }
        .product-media img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
        .product-card:hover .product-media img { transform: scale(1.04); }
        .badge {
            position: absolute; left: 8px; top: 8px;
            padding: 3px 9px; border-radius: var(--pill);
            color: #fff; background: var(--coral); font-weight: 700; font-size: 11px;
        }
        .ribbon {
            position: absolute; left: 0; right: 0; bottom: 0;
            padding: 5px; text-align: center;
            color: #fff; font-weight: 700; font-size: 11px;
            background: linear-gradient(90deg, var(--amber), var(--brand));
        }
        .product-body { padding: 10px 10px 12px; }
        .rating { color: var(--muted); font-size: 11px; margin-bottom: 4px; line-height: 1.4; }
        .rating .star { color: var(--gold); }
        .rating strong { color: var(--ink); }
        .product-title {
            margin: 0 0 8px; font-size: 13px; font-weight: 600;
            line-height: 1.35; min-height: 35px; color: var(--ink);
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }
        .price-row { display: flex; align-items: center; justify-content: space-between; gap: 4px; }
        .price { color: var(--brand); font-size: 16px; font-weight: 800; white-space: nowrap; letter-spacing: -.02em; }
        .old-price { display: block; color: #b0afb8; text-decoration: line-through; font-size: 11px; line-height: 1.2; }
        .details-link {
            flex-shrink: 0; height: 30px; padding: 0 12px;
            display: inline-flex; align-items: center;
            border-radius: var(--pill); color: #fff; background: var(--brand);
            font-weight: 700; font-size: 12px; transition: background .15s;
        }
        .details-link:hover { background: var(--brand-dark); }

        .category-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .category-card {
            min-height: 150px; border-radius: 18px; padding: 18px;
            color: #fff; display: flex; flex-direction: column;
            justify-content: space-between; overflow: hidden;
            position: relative; transition: transform .2s;
        }
        .category-card:hover { transform: translateY(-3px); }
        .category-card:nth-child(4n+1) { background: linear-gradient(135deg, #f47175, #e85d61); }
        .category-card:nth-child(4n+2) { background: linear-gradient(135deg, #5ccfbf, #3bb8a8); }
        .category-card:nth-child(4n+3) { background: linear-gradient(135deg, #7b79d4, #5b59c2); }
        .category-card:nth-child(4n+4) { background: linear-gradient(135deg, #f5bc5c, #eda946); }
        .category-card::after {
            content: ""; position: absolute;
            width: 110px; height: 110px; border-radius: 50%;
            right: -26px; bottom: -26px; background: rgba(255,255,255,.12);
        }
        .category-thumb {
            width: 46px; height: 46px; object-fit: cover;
            border-radius: 13px; background: rgba(255,255,255,.85); padding: 3px;
        }
        .category-card h3 { margin: 8px 0 3px; font-size: 16px; line-height: 1.15; font-weight: 800; }
        .category-card span { font-size: 12px; font-weight: 600; opacity: .85; }

        .download-band { padding: 44px 0; background: var(--ink); color: #fff; }
        .download-panel { display: grid; grid-template-columns: 1fr auto; gap: 28px; align-items: center; }
        .download-panel h2 { margin: 0 0 8px; font-size: clamp(24px, 3.2vw, 36px); font-weight: 800; letter-spacing: -.02em; }
        .download-panel p { margin: 0; color: #9e9daf; font-size: 15px; line-height: 1.55; }
        .store-links { display: flex; flex-wrap: wrap; gap: 10px; }
        .store-badge {
            min-width: 130px; height: 48px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 12px; color: var(--ink); background: #fff;
            font-weight: 800; font-size: 14px; transition: transform .1s;
        }
        .store-badge:hover { transform: scale(1.03); }
        .download-logo { width: 160px; height: auto; max-height: 40px; object-fit: contain; margin-bottom: 14px; }

        .footer { padding: 32px 0 24px; background: var(--bg); border-top: 1px solid var(--line); }
        .footer-grid { display: grid; grid-template-columns: 1.5fr repeat(3, 1fr); gap: 28px; }
        .footer h3 { font-size: 15px; font-weight: 800; margin-bottom: 8px; }
        .footer h4 { font-size: 12px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
        .footer p { color: var(--muted); font-size: 13px; line-height: 1.6; }
        .footer a { color: var(--ink-soft); font-size: 14px; line-height: 2; transition: color .15s; }
        .footer a:hover { color: var(--brand); }

        .page-hero { padding: 32px 0 14px; }
        .page-hero h1 { font-size: clamp(26px, 4vw, 44px); font-weight: 900; letter-spacing: -.02em; }
        .page-hero p { margin: 4px 0 0; color: var(--muted); font-size: 15px; }
        .filter-bar { display: flex; gap: 10px; align-items: center; margin: 16px 0 12px; }
        .filter-bar input {
            flex: 1; height: 44px; border: 1.5px solid var(--line); border-radius: var(--pill);
            background: #fff; padding: 0 18px; font: inherit; font-size: 14px; color: var(--ink);
        }
        .filter-bar input:focus { outline: none; border-color: var(--brand); }
        .filter-bar input::placeholder { color: #b5b4be; }
        .cat-tabs {
            display: flex; gap: 7px; overflow-x: auto;
            padding-bottom: 4px; margin-bottom: 16px; scrollbar-width: none;
        }
        .cat-tabs::-webkit-scrollbar { display: none; }
        .cat-tab {
            flex-shrink: 0; height: 34px; padding: 0 14px;
            display: inline-flex; align-items: center;
            border-radius: var(--pill); border: 1.5px solid var(--line);
            background: #fff; color: var(--ink-soft);
            font-weight: 600; font-size: 13px;
            white-space: nowrap; transition: all .15s;
        }
        .cat-tab:hover { border-color: var(--brand); color: var(--brand); }
        .cat-tab.active { background: var(--ink); color: #fff; border-color: var(--ink); }

        .pagination { margin: 24px 0 8px; }
        .pagination-shell {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            flex-wrap: wrap;
            width: 100%;
        }
        .pagination-summary {
            color: var(--muted);
            font-size: 14px;
            text-align: center;
        }
        .pagination-links {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            flex-wrap: wrap;
            max-width: 100%;
        }
        .pagination-links span,
        .pagination-links a {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 36px; height: 36px; padding: 0 4px;
            border-radius: var(--r-sm); font-weight: 600; font-size: 13px;
        }
        .pagination-links a { color: var(--ink-soft); background: var(--soft); transition: all .15s; }
        .pagination-links a:hover { background: var(--ink); color: #fff; }
        .pagination-links .is-current { background: var(--brand); color: #fff; font-weight: 800; }
        .pagination-links .is-disabled { color: #c2c1ca; background: transparent; }

        .product-detail { display: grid; grid-template-columns: 1fr 420px; gap: 36px; padding: 32px 0 44px; }
        .gallery-main { border-radius: 22px; overflow: hidden; background: var(--soft); aspect-ratio: 1 / .86; }
        .gallery-main img { width: 100%; height: 100%; object-fit: contain; }
        .stat-pills { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0 18px; }
        .stat-pill { padding: 6px 12px; border-radius: var(--pill); background: var(--soft); color: var(--ink-soft); font-weight: 700; font-size: 13px; }
        .limit-box {
            display: flex; gap: 12px; align-items: center;
            padding: 14px 16px; border-radius: var(--r);
            border: 1.5px solid #b3bcea; background: #f0f4ff; color: #3b46a0; margin: 14px 0; font-size: 14px;
        }
        .color-dot {
            width: 28px; height: 28px; display: inline-block;
            border-radius: 50%; border: 3px solid #fff;
            box-shadow: 0 0 0 1.5px var(--line); margin-right: 5px;
        }

        .info-card { border: 1px solid var(--line); border-radius: var(--r); padding: 32px; background: #fff; box-shadow: var(--shadow-sm); text-align: center; }
        .info-card h2 { font-size: 20px; margin-bottom: 6px; }
        .info-card p { color: var(--muted); font-size: 14px; }
        .description { color: var(--muted); font-size: 16px; line-height: 1.75; }
        .article { max-width: 860px; padding: 32px 0 48px; color: var(--muted); font-size: 16px; line-height: 1.8; }
        .article h2, .article h3 { color: var(--ink); }
        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 22px 0 44px; }
        .footer-bottom {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 13px;
            text-align: center;
        }
        .footer-bottom a {
            color: var(--ink);
            font-weight: 700;
        }
        .download-modal {
            position: fixed;
            inset: 0;
            z-index: 120;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(20, 19, 30, .62);
            backdrop-filter: blur(12px);
        }
        .download-modal.open { display: flex; }
        .download-dialog {
            width: min(100%, 520px);
            border-radius: 28px;
            background:
                radial-gradient(circle at top right, rgba(242,106,46,.18), transparent 34%),
                radial-gradient(circle at bottom left, rgba(91,89,194,.12), transparent 28%),
                #fff;
            box-shadow: 0 24px 80px rgba(18, 17, 28, .28);
            border: 1px solid rgba(255,255,255,.65);
            overflow: hidden;
        }
        .download-dialog-head {
            padding: 24px 24px 10px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
        .download-dialog-head h3 {
            font-size: 28px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: -.03em;
            margin-bottom: 10px;
        }
        .download-dialog-head p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }
        .download-close {
            width: 40px;
            height: 40px;
            border: 0;
            border-radius: 50%;
            background: var(--soft);
            color: var(--ink);
            font-size: 22px;
            line-height: 1;
        }
        .download-dialog-body {
            padding: 14px 24px 24px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .download-option {
            min-height: 200px;
            border-radius: 22px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid var(--line);
            background: #fff;
            transition: transform .15s, box-shadow .15s, border-color .15s;
        }
        .download-option:hover {
            transform: translateY(-2px);
            border-color: rgba(242,106,46,.35);
            box-shadow: var(--shadow);
        }
        .download-option strong {
            font-size: 22px;
            line-height: 1.05;
            font-weight: 900;
        }
        .download-option p {
            margin-top: 10px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
        }
        .download-option span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            border-radius: 999px;
            background: var(--ink);
            color: #fff;
            font-weight: 800;
            font-size: 14px;
        }
        .download-option.ios {
            background: linear-gradient(180deg, #f8f8fb, #ffffff);
        }
        .download-option.android {
            background: linear-gradient(180deg, #fff5ef, #ffffff);
        }

        @media (max-width: 960px) {
            .hero-grid, .download-panel, .footer-grid, .contact-grid { grid-template-columns: 1fr; }
            .product-detail { grid-template-columns: 1fr; }
            .product-grid { grid-template-columns: repeat(3, 1fr); gap: 12px; }
            .category-grid { grid-template-columns: repeat(3, 1fr); }
            .nav-links { display: none; }
            .mobile-menu-btn { display: block; }
            .hero-band { padding: 28px 0 36px; }
            .hero-copy h1 { font-size: 38px; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 20px; }
        }

        @media (max-width: 600px) {
            .shell { width: calc(100% - 20px); }
            .nav { height: 56px; gap: 12px; }
            .brand-logo { width: 125px; max-height: 30px; }
            .hero-band { padding: 18px 0 24px; }
            .hero-grid { gap: 16px; }
            .hero-copy h1 { font-size: 28px; line-height: 1; }
            .hero-copy p { font-size: 13px; margin: 8px 0 16px; max-width: 100%; }
            .pill-button { height: 38px; padding: 0 16px; font-size: 13px; }
            .ghost-button { height: 34px; padding: 0 12px; font-size: 12px; }
            .hero-actions .pill-button, .hero-actions .ghost-button { flex: 1; justify-content: center; }
            .section { padding: 18px 0; }
            .section-head { margin-bottom: 12px; }
            .section-head h2 { font-size: 18px; }
            .section-head p { font-size: 12px; }
            .product-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .product-card { border-radius: 12px; }
            .badge { left: 6px; top: 6px; padding: 2px 7px; font-size: 10px; }
            .ribbon { padding: 4px; font-size: 10px; }
            .product-body { padding: 8px 8px 10px; }
            .rating { font-size: 10px; margin-bottom: 2px; }
            .product-title { font-size: 12px; min-height: 32px; margin-bottom: 6px; }
            .price { font-size: 14px; }
            .old-price { font-size: 10px; }
            .details-link { height: 26px; padding: 0 9px; font-size: 10px; }
            .category-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .category-card { min-height: 100px; border-radius: 14px; padding: 12px; }
            .category-thumb { width: 34px; height: 34px; border-radius: 9px; }
            .category-card h3 { font-size: 13px; margin: 4px 0 2px; }
            .category-card span { font-size: 10px; }
            .category-card::after { width: 70px; height: 70px; right: -16px; bottom: -16px; }
            .page-hero { padding: 18px 0 10px; }
            .page-hero h1 { font-size: 22px; }
            .page-hero p { font-size: 13px; }
            .filter-bar { margin: 10px 0 8px; }
            .filter-bar input { height: 38px; font-size: 13px; padding: 0 14px; }
            .filter-bar .pill-button { width: auto; min-width: 60px; height: 38px; }
            .cat-tabs { gap: 5px; margin-bottom: 10px; }
            .cat-tab { height: 30px; padding: 0 10px; font-size: 11px; }
            .download-band { padding: 28px 0; }
            .download-panel h2 { font-size: 20px; }
            .download-panel p { font-size: 13px; }
            .download-logo { width: 120px; margin-bottom: 10px; }
            .store-links { gap: 8px; width: 100%; }
            .store-badge { flex: 1; min-width: 100px; height: 40px; font-size: 13px; border-radius: 10px; }
            .footer { padding: 20px 0 16px; }
            .footer-grid { grid-template-columns: 1fr; gap: 14px; }
            .footer h3 { font-size: 14px; }
            .footer h4 { font-size: 11px; }
            .footer p { font-size: 12px; }
            .footer a { font-size: 13px; line-height: 1.8; }
            .pagination-shell { gap: 12px; }
            .pagination-summary { width: 100%; font-size: 12px; }
            .pagination-links span,
            .pagination-links a { min-width: 30px; height: 30px; font-size: 12px; border-radius: 8px; }
            .contact-grid { grid-template-columns: 1fr; }
            .download-dialog {
                width: min(100%, 420px);
                border-radius: 22px;
            }
            .download-dialog-head { padding: 20px 18px 8px; }
            .download-dialog-head h3 { font-size: 24px; }
            .download-dialog-body {
                padding: 12px 18px 18px;
                grid-template-columns: 1fr;
            }
            .download-option { min-height: 150px; border-radius: 18px; }
        }
    </style>
    @stack('head')
</head>
<body>
@php
    $iosLink = $downloadLinks['ios'] ?? '#';
    $androidLink = $downloadLinks['android'] ?? '#';
@endphp
<header class="topbar">
    <nav class="shell nav" aria-label="Əsas naviqasiya">
        <a class="brand" href="{{ route('storefront.home') }}" aria-label="Teymur Store ana səhifə">
            <img class="brand-logo" src="{{ asset('assets/brand/logo-color.png') }}" alt="Teymur Store">
        </a>
        <div class="nav-links">
            <a href="{{ route('storefront.products') }}">Məhsullar</a>
            <a href="{{ route('storefront.listings') }}">Elanlar</a>
            <a href="{{ route('storefront.categories') }}">Kateqoriyalar</a>
            <a href="{{ route('storefront.about') }}">Haqqımızda</a>
            <a href="{{ route('storefront.contact') }}">Əlaqə</a>
            <a class="pill-button js-download-trigger" href="#" data-download-source="header">Tətbiqi yüklə</a>
        </div>
        <button class="mobile-menu-btn" aria-label="Menyu" onclick="this.nextElementSibling.classList.toggle('open')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <div class="mobile-nav">
            <a href="{{ route('storefront.products') }}">Məhsullar</a>
            <a href="{{ route('storefront.listings') }}">Elanlar</a>
            <a href="{{ route('storefront.categories') }}">Kateqoriyalar</a>
            <a href="{{ route('storefront.about') }}">Haqqımızda</a>
            <a href="{{ route('storefront.contact') }}">Əlaqə</a>
            <a class="pill-button js-download-trigger" href="#" data-download-source="mobile-menu" style="margin-top:6px;width:100%;justify-content:center">Tətbiqi yüklə</a>
        </div>
    </nav>
</header>

<main>
    @yield('content')
</main>

<section class="download-band" id="download">
    <div class="shell download-panel">
        <div>
            <img class="download-logo" src="{{ asset('assets/brand/logo-white.png') }}" alt="Teymur Store">
            <h2>Sifariş üçün tətbiqi yüklə</h2>
            <p>Veb səhifə yalnız məhsullara baxış üçündür. Səbət, profil, seçilmişlər və sifariş prosesi təhlükəsiz şəkildə mobil tətbiqdə davam edir.</p>
        </div>
        <div class="store-links">
            <a class="store-badge" href="{{ $iosLink }}" rel="nofollow noopener">App Store</a>
            <a class="store-badge" href="{{ $androidLink }}" rel="nofollow noopener">Google Play</a>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="shell footer-grid">
        <div>
            <h3>Teymur Store</h3>
            <p>Mobil tətbiq üzərindən işləyən e-ticarət mağazası. Veb tərəf məhsul və brend görünməsi üçün hazırlanıb.</p>
        </div>
        <div>
            <h4>Bölmələr</h4>
            <a href="{{ route('storefront.products') }}">Məhsullar</a><br>
            <a href="{{ route('storefront.categories') }}">Kateqoriyalar</a><br>
            <a href="{{ route('storefront.terms') }}">Qaydalar</a>
        </div>
        <div>
            <h4>Əlaqə</h4>
            @if($settings?->phone_number_1)<a href="tel:{{ preg_replace('/\D+/', '', $settings->phone_number_1) }}">{{ $settings->phone_number_1 }}</a><br>@endif
            @if($settings?->whatsapp_number)<a href="https://wa.me/{{ preg_replace('/\D+/', '', $settings->whatsapp_number) }}" rel="noopener nofollow">WhatsApp</a><br>@endif
            @if($settings?->address)<span style="font-size:13px;color:var(--muted)">{{ $settings->address }}</span>@endif
        </div>
        <div>
            <h4>Sosial</h4>
            @if($settings?->instagram_url)<a href="{{ $settings->instagram_url }}" rel="noopener nofollow">Instagram</a><br>@endif
            @if($settings?->tiktok_url)<a href="{{ $settings->tiktok_url }}" rel="noopener nofollow">TikTok</a><br>@endif
            @if($settings?->google_map_url)<a href="{{ $settings->google_map_url }}" rel="noopener nofollow">Xəritədə bax</a>@endif
        </div>
    </div>
    <div class="shell footer-bottom">
        Developed by <a href="https://nss.az/portfolio/teymur-store-e-ticaret-mobil-tetbiqi" rel="noopener nofollow" target="_blank">NS Studio</a>
    </div>
</footer>

<div class="download-modal" id="downloadModal" aria-hidden="true">
    <div class="download-dialog" role="dialog" aria-modal="true" aria-labelledby="downloadModalTitle">
        <div class="download-dialog-head">
            <div>
                <h3 id="downloadModalTitle">Tətbiqi yüklə</h3>
                <p>Məhsullara baxış veb saytında qalır. Sifariş, səbət və hesab əməliyyatları mobil tətbiqdə təhlükəsiz şəkildə davam edir.</p>
            </div>
            <button class="download-close" type="button" aria-label="Bağla" data-close-download-modal>×</button>
        </div>
        <div class="download-dialog-body">
            <a class="download-option ios" href="{{ $iosLink }}" rel="nofollow noopener">
                <div>
                    <strong>iPhone üçün</strong>
                    <p>App Store üzərindən Teymur Store tətbiqini aç və sifarişini rahat şəkildə tamamla.</p>
                </div>
                <span>App Store</span>
            </a>
            <a class="download-option android" href="{{ $androidLink }}" rel="nofollow noopener">
                <div>
                    <strong>Android üçün</strong>
                    <p>Google Play keçidi ilə tətbiqi yüklə, məhsulları seç və sifarişini tətbiqdən et.</p>
                </div>
                <span>Google Play</span>
            </a>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('downloadModal');
        if (!modal) return;

        const openers = document.querySelectorAll('.js-download-trigger');
        const closeButton = modal.querySelector('[data-close-download-modal]');
        const dialog = modal.querySelector('.download-dialog');

        function openModal(event) {
            if (event) event.preventDefault();
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }

        function closeModal() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        }

        openers.forEach((opener) => opener.addEventListener('click', openModal));
        closeButton.addEventListener('click', closeModal);
        modal.addEventListener('click', (event) => {
            if (!dialog.contains(event.target)) {
                closeModal();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('open')) {
                closeModal();
            }
        });
    })();
</script>
</body>
</html>
