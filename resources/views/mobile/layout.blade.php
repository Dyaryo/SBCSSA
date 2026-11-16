<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#071018">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Sabong Report">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/rooster-mascot.svg') }}">
    <link rel="icon" href="{{ asset_v('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('icons/rooster-mascot.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset_v('css/mobile.css') }}">
    <title>@yield('title', 'Report') · E-Sabong</title>
</head>
<body>
<main class="m-shell">
    @yield('content')
</main>

<script src="{{ asset_v('js/live-refresh.js') }}" defer></script>
<script src="{{ asset_v('js/mobile-report.js') }}" defer></script>
</body>
</html>
