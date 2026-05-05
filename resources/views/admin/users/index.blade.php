@extends('layouts.app')

@section('content')
    <section class="edit-head">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Users</h1>
        </div>
        <a class="primary-button" href="{{ route('admin.users.create') }}">Create user</a>
    </section>

    <section class="user-table-wrap">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Tasks</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge role-{{ $user->role }}">{{ ucfirst($user->role) }}</span></td>
                        <td>{{ $user->tasks_count }}</td>
                        <td>{{ $user->created_at?->format('M j, Y') }}</td>
                        <td><a class="button-link compact" href="{{ route('admin.users.edit', $user) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
