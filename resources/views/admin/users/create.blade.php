@extends('layouts.app')

@section('content')
    <section class="edit-head">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Create User</h1>
        </div>
        <a class="button-link" href="{{ route('admin.users.index') }}">Users</a>
    </section>

    <form method="POST" action="{{ route('admin.users.store') }}" class="edit-form">
        @csrf

        <div class="detail-grid">
            <label>
                Username
                <input type="text" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
            </label>
            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            </label>
            <label>
                Role
                <select name="role" required>
                    <option value="user" @selected(old('role', 'user') === 'user')>User</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                </select>
            </label>
            <label>
                Password
                <input type="password" name="password" autocomplete="new-password" required>
            </label>
            <label>
                Confirm password
                <input type="password" name="password_confirmation" autocomplete="new-password" required>
            </label>
        </div>

        <div class="form-actions">
            <button class="primary-button" type="submit">Create user</button>
            <a class="button-link" href="{{ route('admin.users.index') }}">Cancel</a>
        </div>
    </form>
@endsection
