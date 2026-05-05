<?php

namespace App\Http\Controllers;

use App\Services\TaskNudgeService;
use Illuminate\Http\JsonResponse;

class ReminderController extends Controller
{
    public function __invoke(TaskNudgeService $nudges): JsonResponse
    {
        $now = now();

        $payload = $nudges->dueTasksForUser(auth()->id())
            ->map(fn ($task): array => $nudges->apiPayload($nudges->markNudged($task)));

        return response()->json([
            'tasks' => $payload,
            'checked_at' => $now->toIso8601String(),
        ]);
    }
}
