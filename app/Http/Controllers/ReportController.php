<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(): View
    {
        $userId = Auth::id();

        Task::releaseExpiredSnoozesFor($userId);

        $tasks = Task::query()
            ->forUser($userId)
            ->latest()
            ->get();

        $openTasks = $tasks->whereNotIn('status', ['completed']);
        $actionableTasks = $tasks->whereNotIn('status', ['completed', 'blocked']);
        $completedTasks = $tasks->where('status', 'completed');
        $now = now();

        $metrics = [
            [
                'label' => 'Total',
                'value' => $tasks->count(),
                'tone' => 'neutral',
            ],
            [
                'label' => 'Open',
                'value' => $openTasks->count(),
                'tone' => 'teal',
            ],
            [
                'label' => 'Completed',
                'value' => $completedTasks->count(),
                'tone' => 'green',
            ],
            [
                'label' => 'Overdue',
                'value' => $actionableTasks
                    ->filter(fn (Task $task): bool => $task->due_at !== null && $task->due_at->lt($now))
                    ->count(),
                'tone' => 'coral',
            ],
            [
                'label' => 'Due 48h',
                'value' => $actionableTasks
                    ->filter(fn (Task $task): bool => $task->due_at !== null && $task->due_at->between($now, $now->copy()->addHours(48)))
                    ->count(),
                'tone' => 'amber',
            ],
            [
                'label' => 'Nudges',
                'value' => $tasks->sum('nudge_count'),
                'tone' => 'blue',
            ],
        ];

        return view('reports.show', [
            'metrics' => $metrics,
            'statusRows' => $this->rowsFor(Task::STATUSES, $tasks, 'status'),
            'priorityRows' => $this->rowsFor(Task::PRIORITIES, $tasks, 'priority'),
            'sourceRows' => $this->rowsFor(Task::SOURCES, $tasks, 'source'),
            'completionTrend' => $this->completionTrend($tasks),
            'nudgeHeavyTasks' => $tasks
                ->filter(fn (Task $task): bool => $task->nudge_count > 0)
                ->sortByDesc('nudge_count')
                ->take(6)
                ->values(),
            'deadlineTasks' => $actionableTasks
                ->filter(fn (Task $task): bool => $task->due_at !== null)
                ->sortBy('due_at')
                ->take(6)
                ->values(),
        ]);
    }

    private function rowsFor(array $labels, Collection $tasks, string $field): array
    {
        $counts = collect($labels)
            ->mapWithKeys(fn (string $label): array => [$label => $tasks->where($field, $label)->count()]);

        $max = max(1, $counts->max());

        return $counts
            ->map(fn (int $count, string $label): array => [
                'label' => $label,
                'count' => $count,
                'percent' => round(($count / $max) * 100),
            ])
            ->values()
            ->all();
    }

    private function completionTrend(Collection $tasks): array
    {
        $counts = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($tasks): array {
                $date = now()->subDays($daysAgo);

                return [
                    'label' => $date->format('D'),
                    'date' => $date->format('M j'),
                    'count' => $tasks
                        ->filter(fn (Task $task): bool => $task->completed_at !== null && $task->completed_at->isSameDay($date))
                        ->count(),
                ];
            });

        $max = max(1, $counts->max('count'));

        return $counts
            ->map(fn (array $day): array => array_merge($day, [
                'percent' => round(($day['count'] / $max) * 100),
            ]))
            ->all();
    }
}
