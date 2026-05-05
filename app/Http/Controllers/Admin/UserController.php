<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->withCount('tasks')
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 ELSE 1 END")
            ->orderBy('username')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:80', 'alpha_dash:ascii', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        User::query()->create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password_hash' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:80', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($user)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if ($this->wouldRemoveLastAdmin($user, $validated['role'])) {
            return back()
                ->withErrors(['role' => 'At least one admin user must remain.'])
                ->withInput();
        }

        $updates = [
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $updates['password_hash'] = Hash::make($validated['password']);
        }

        $user->update($updates);

        if ($request->user()->is($user) && $validated['role'] !== 'admin') {
            return redirect()->route('dashboard')->with('status', 'Your role was updated.');
        }

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    private function wouldRemoveLastAdmin(User $user, string $newRole): bool
    {
        return $user->role === 'admin'
            && $newRole !== 'admin'
            && User::query()->where('role', 'admin')->count() <= 1;
    }
}
