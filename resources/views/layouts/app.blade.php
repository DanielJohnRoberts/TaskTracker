<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f766e">
    <title>{{ config('app.name', 'Top 3 Tasks') }}</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    <script src="{{ asset('assets/app.js') }}" defer></script>
</head>
<body
    @auth
        data-reminder-url="{{ route('api.reminders', absolute: false) }}"
        data-dashboard-url="{{ route('dashboard', absolute: false) }}"
        data-push-store-url="{{ route('api.push.store', absolute: false) }}"
        data-push-delete-url="{{ route('api.push.destroy', absolute: false) }}"
        data-vapid-public-key="{{ config('services.webpush.public_key') }}"
    @endauth
>
    <header class="topbar">
        <a class="brand" href="{{ route('dashboard') }}">Top 3</a>
        <nav class="nav">
            @auth
                <span>{{ Auth::user()->username }}</span>
                <a href="{{ route('reports') }}">Reports</a>
                @if (Auth::user()->isAdmin())
                    <a href="{{ route('admin.settings.edit') }}">Settings</a>
                    <a href="{{ route('admin.users.index') }}">Users</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="link-button" type="submit">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}">Log in</a>
            @endauth
        </nav>
    </header>

    <main class="page">
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-box">
                <strong>Check these fields:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
