<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $userId = Auth::id();

        Task::releaseExpiredSnoozesFor($userId);

        $topTasks = Task::query()
            ->forUser($userId)
            ->dashboardEligible()
            ->ranked()
            ->limit(3)
            ->get();

        $urgentTasks = Task::query()
            ->forUser($userId)
            ->dashboardEligible()
            ->where(function ($query): void {
                $query->where('is_urgent', true)->orWhere('priority', 'urgent');
            })
            ->ranked()
            ->limit(6)
            ->get();

        $recentTasks = Task::query()
            ->forUser($userId)
            ->where('status', '!=', 'completed')
            ->latest()
            ->limit(6)
            ->get();

        $counts = [
            'active' => Task::query()
                ->forUser($userId)
                ->whereIn('status', ['captured', 'active', 'nudged', 'escalated'])
                ->count(),
            'completed' => Task::query()
                ->forUser($userId)
                ->where('status', 'completed')
                ->count(),
            'snoozed' => Task::query()
                ->forUser($userId)
                ->where('status', 'snoozed')
                ->count(),
            'overdue' => Task::query()
                ->forUser($userId)
                ->whereNotIn('status', ['completed', 'blocked'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count(),
        ];

        return view('dashboard', compact('topTasks', 'urgentTasks', 'recentTasks', 'counts'));
    }
}
