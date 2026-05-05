<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class TaskNudgeService
{
    public function dueTasksForUser(int $userId, int $limit = 3): Collection
    {
        $now = now();

        Task::releaseExpiredSnoozesFor($userId);

        return Task::query()
            ->forUser($userId)
            ->dashboardEligible()
            ->where(function ($query) use ($now): void {
                $query
                    ->where(function ($query) use ($now): void {
                        $query->whereNotNull('reminder_at')->where('reminder_at', '<=', $now);
                    })
                    ->orWhere(function ($query) use ($now): void {
                        $query->whereNotNull('due_at')->where('due_at', '<=', $now->copy()->addHours(2));
                    })
                    ->orWhere(function ($query) use ($now): void {
                        $query->whereNull('due_at')->where('created_at', '<=', $now->copy()->subDay());
                    });
            })
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('last_nudged_at')
                    ->orWhere('last_nudged_at', '<=', $now->copy()->subMinutes(30));
            })
            ->ranked()
            ->limit($limit)
            ->get();
    }

    public function nextStatus(Task $task): string
    {
        $now = now();

        $closeHardDeadline = $task->deadline_type === 'hard'
            && $task->due_at !== null
            && $task->due_at->lte($now->copy()->addDay());

        $ignoredTooLong = $task->nudge_count >= 2
            || ($task->last_nudged_at !== null && $task->last_nudged_at->lte($now->copy()->subDay()))
            || ($task->last_nudged_at === null && $task->created_at->lte($now->copy()->subDays(2)));

        return ($closeHardDeadline || $ignoredTooLong) ? 'escalated' : 'nudged';
    }

    public function markNudged(Task $task, ?string $status = null): Task
    {
        $task->forceFill([
            'status' => $status ?? $this->nextStatus($task),
            'last_nudged_at' => now(),
            'nudge_count' => $task->nudge_count + 1,
        ])->save();

        return $task;
    }

    public function apiPayload(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status,
            'due_at' => optional($task->due_at)->toIso8601String(),
            'is_urgent' => $task->is_urgent,
            'nudge_count' => $task->nudge_count,
        ];
    }

    public function pushPayload(Task $task, string $status): array
    {
        $prefix = $status === 'escalated' ? 'Escalated' : 'Task nudge';
        $body = $task->due_at?->isPast()
            ? 'Overdue'
            : ($task->due_at ? 'Due '.$task->due_at->format('M j, H:i') : 'Still waiting');

        return [
            'title' => $prefix,
            'body' => $body.': '.$task->title,
            'url' => AppSetting::publicAppUrl().route('dashboard', absolute: false),
            'tag' => 'top3-task-'.$task->id,
            'task_id' => $task->id,
            'status' => $status,
        ];
    }
}
