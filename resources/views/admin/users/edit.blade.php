@extends('layouts.app')

@section('content')
    <section class="edit-head">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Edit User</h1>
        </div>
        <a class="button-link" href="{{ route('admin.users.index') }}">Users</a>
    </section>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="edit-form">
        @csrf
        @method('PUT')

        <div class="detail-grid">
            <label>
                Username
                <input type="text" name="username" value="{{ old('username', $user->username) }}" autocomplete="username" required autofocus>
            </label>
            <label>
                Email
                <input type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
            </label>
            <label>
                Role
                <select name="role" required>
                    <option value="user" @selected(old('role', $user->role) === 'user')>User</option>
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                </select>
            </label>
            <label>
                New password
                <input type="password" name="password" autocomplete="new-password">
            </label>
            <label>
                Confirm password
                <input type="password" name="password_confirmation" autocomplete="new-password">
            </label>
        </div>

        <div class="form-actions">
            <button class="primary-button" type="submit">Save user</button>
            <a class="button-link" href="{{ route('admin.users.index') }}">Cancel</a>
        </div>
    </form>
@endsection
