<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedTask($request);
        $validated['user_id'] = Auth::id();
        $validated['status'] = 'captured';
        $validated['is_urgent'] = $request->boolean('is_urgent') || ($validated['priority'] ?? 'normal') === 'urgent';

        // Tasks captured without a reminder are resurfaced later instead of being left inert.
        if (empty($validated['reminder_at'])) {
            $validated['reminder_at'] = now()->addHours(4);
        }

        Task::query()->create($validated);

        return back()->with('status', 'Task captured.');
    }

    public function edit(Task $task): View
    {
        $this->authorizeTask($task);

        return view('tasks.edit', compact('task'));
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

        $validated = $this->validatedTask($request, includeStatus: true);
        $validated['is_urgent'] = $request->boolean('is_urgent') || ($validated['priority'] ?? 'normal') === 'urgent';
        $validated['completed_at'] = ($validated['status'] ?? $task->status) === 'completed' ? ($task->completed_at ?? now()) : null;

        if (($validated['status'] ?? null) !== 'snoozed') {
            $validated['snoozed_until'] = null;
        }

        if (($validated['status'] ?? null) !== 'blocked') {
            $validated['blocked_reason'] = null;
        }

        $task->update($validated);

        return redirect()->route('dashboard')->with('status', 'Task updated.');
    }

    public function complete(Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('status', 'Task completed.');
    }

    public function toggleUrgent(Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

        $makeUrgent = ! $task->is_urgent;

        $task->update([
            'is_urgent' => $makeUrgent,
            'priority' => $makeUrgent ? 'urgent' : ($task->priority === 'urgent' ? 'normal' : $task->priority),
            'status' => $task->status === 'captured' && $makeUrgent ? 'active' : $task->status,
        ]);

        return back()->with('status', $makeUrgent ? 'Marked urgent.' : 'Urgent removed.');
    }

    public function snooze(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

        $validated = $request->validate([
            'minutes' => ['required', 'integer', Rule::in([60, 240, 1440, 4320])],
        ]);

        $task->update([
            'status' => 'snoozed',
            'snoozed_until' => now()->addMinutes($validated['minutes']),
        ]);

        return back()->with('status', 'Task snoozed.');
    }

    public function block(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

        $validated = $request->validate([
            'blocked_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $task->update([
            'status' => 'blocked',
            'blocked_reason' => $validated['blocked_reason'] ?: 'Blocked from dashboard',
        ]);

        return back()->with('status', 'Task blocked.');
    }

    public function unblock(Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

        $task->update([
            'status' => 'active',
            'blocked_reason' => null,
        ]);

        return back()->with('status', 'Task unblocked.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

        $task->delete();

        return back()->with('status', 'Task deleted.');
    }

    private function validatedTask(Request $request, bool $includeStatus = false): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', Rule::in(Task::PRIORITIES)],
            'due_at' => ['nullable', 'date'],
            'deadline_type' => ['nullable', Rule::in(Task::DEADLINE_TYPES)],
            'reminder_at' => ['nullable', 'date'],
            'source' => ['nullable', Rule::in(Task::SOURCES)],
            'notes' => ['nullable', 'string'],
            'snoozed_until' => ['nullable', 'date'],
            'blocked_reason' => ['nullable', 'string'],
            'recurrence_type' => ['nullable', Rule::in(Task::RECURRENCE_TYPES)],
            'recurrence_interval' => ['nullable', 'string', 'max:50'],
        ];

        if ($includeStatus) {
            $rules['status'] = ['required', Rule::in(Task::STATUSES)];
        }

        $validated = $request->validate($rules);

        return array_merge([
            'priority' => 'normal',
            'deadline_type' => 'none',
            'source' => 'other',
            'recurrence_type' => 'none',
        ], $validated);
    }

    private function authorizeTask(Task $task): void
    {
        abort_unless($task->user_id === Auth::id(), 404);
    }
}
