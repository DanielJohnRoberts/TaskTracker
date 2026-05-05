@extends('layouts.app')

@section('content')
    <section class="auth-shell">
        <div class="auth-panel">
            <h1>Log in</h1>
            <form method="POST" action="{{ route('login') }}" class="form-stack">
                @csrf
                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </label>
                <label>
                    Password
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>
                <button class="primary-button" type="submit">Log in</button>
            </form>
        </div>
    </section>
@endsection
