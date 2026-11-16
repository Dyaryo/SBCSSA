<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · CAMSUR SPORTS ARENA</title>
    <link rel="stylesheet" href="{{ asset_v('css/app.css') }}">
    <link rel="icon" href="{{ asset_v('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset_v('icons/rooster-mascot.svg') }}" type="image/svg+xml">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        @php
            // Drop the venue's own rooster artwork at public/icons/rooster-photo.png
            // (or .jpg/.webp) and it replaces the drawn mascot with no code change.
            $customMark = collect(['rooster-photo.png', 'rooster-photo.jpg', 'rooster-photo.webp'])
                ->first(fn ($file) => file_exists(public_path('icons/'.$file)));
        @endphp
        <div class="auth-brand">
            @if ($customMark)
                <img class="auth-mark auth-mark-photo" src="{{ asset_v('icons/'.$customMark) }}"
                     alt="Camsur Sports Arena">
            @else
                <img class="auth-mark" src="{{ asset_v('icons/rooster-mascot.svg') }}"
                     alt="Camsur Sports Arena rooster" width="200" height="200">
            @endif
            <h1>CAMSUR SPORTS ARENA</h1>
            <p class="muted small">Supervisor · Game Master · POS Teller</p>
        </div>

        @if ($errors->any())
            <div class="flash err">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf

            <label class="field">
                <span>Username</span>
                <input class="input" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
            </label>

            <label class="field">
                <span>Password</span>
                <input class="input" type="password" name="password" required autocomplete="current-password">
            </label>

            <label class="row small muted" style="margin-bottom:14px">
                <input type="checkbox" name="remember" value="1"> Keep this terminal signed in
            </label>

            <input type="hidden" name="device_uuid" data-device-uuid value="">

            <button type="submit" class="btn btn-primary btn-block">Sign in</button>

            <p class="muted small" style="margin-top:10px">
                Terminal fingerprint <span class="mono" data-device-label>…</span>
            </p>
        </form>

        <div class="auth-play-money">
            <strong>Play money only</strong>
            <span>
                Credits are virtual, have no cash value, and cannot be bought, sold, redeemed or
                exchanged for physical cash. This console never accepts or pays out real money.
            </span>
        </div>
    </div>
</div>

<script src="{{ asset_v('js/app.js') }}"></script>
</body>
</html>
