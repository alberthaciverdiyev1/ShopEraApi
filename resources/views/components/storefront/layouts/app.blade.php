@props([
    'title' => config('app.name'),
    'description' => null,
    'categories' => [],
    'popup' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    <link rel="icon" href="/assets/images/favicon.png">
    <link rel="shortcut icon" href="/assets/images/favicon.png">
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/icomoon-one.css">
    <link rel="stylesheet" href="/assets/css/animate.css">
    <link rel="stylesheet" href="/assets/css/splitting.css">
    <link rel="stylesheet" href="/assets/css/magnific-popup.css">
    <link rel="stylesheet" href="/assets/css/meanmenu.css">
    <link rel="stylesheet" href="/assets/css/nice-select.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/snaker-theme.css">
    @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])
</head>
<body class="storefront">
    <x-storefront.header :categories="$categories" />

    <main class="storefront-main">
        {{ $slot }}
    </main>

    <x-storefront.footer />

    <x-storefront.live-chat />

    <x-storefront.popup-modal :popup="$popup" />
</body>
</html>
