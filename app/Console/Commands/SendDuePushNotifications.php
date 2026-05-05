<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TaskNudgeService;
use App\Services\WebPushService;
use Illuminate\Console\Command;

class SendDuePushNotifications extends Command
{
    protected $signature = 'tasks:send-push {--dry-run : Count due pushes without sending or changing tasks}';

    protected $description = 'Send Web Push nudges for due tasks.';

    public function handle(TaskNudgeService $nudges, WebPushService $pushes): int
    {
        if (! $this->option('dry-run') && ! $pushes->configured()) {
            $this->error('Web Push is not configured. Set VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, and VAPID_SUBJECT.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $dueTasks = 0;
        $attempted = 0;
        $sent = 0;
        $expired = 0;

        User::query()
            ->whereHas('pushSubscriptions')
            ->with('pushSubscriptions')
            ->chunkById(100, function ($users) use ($nudges, $pushes, $dryRun, &$dueTasks, &$attempted, &$sent, &$expired): void {
                foreach ($users as $user) {
                    foreach ($nudges->dueTasksForUser($user->id) as $task) {
                        $dueTasks++;

                        if ($dryRun) {
                            continue;
                        }

                        $status = $nudges->nextStatus($task);
                        $result = $pushes->sendTask($task->loadMissing('user.pushSubscriptions'), $nudges->pushPayload($task, $status));

                        $attempted += $result['attempted'];
                        $sent += $result['sent'];
                        $expired += $result['expired'];

                        if ($result['attempted'] > 0) {
                            $nudges->markNudged($task, $status);
                        }
                    }
                }
            });

        $this->info("Due tasks: {$dueTasks}; attempted pushes: {$attempted}; accepted pushes: {$sent}; expired subscriptions: {$expired}.");

        return self::SUCCESS;
    }
}
