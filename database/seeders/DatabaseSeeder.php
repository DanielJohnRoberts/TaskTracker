<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        AppSetting::setPublicAppUrl(config('app.url'));

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'role' => 'admin',
                'password_hash' => Hash::make('password'),
            ],
        );

        $basicUser = User::query()->updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'username' => 'basicuser',
                'role' => 'user',
                'password_hash' => Hash::make('password'),
            ],
        );

        $this->seedExampleTasks($basicUser);
        $this->seedExampleTasks($admin);
    }

    private function seedExampleTasks(User $user): void
    {
        $examples = [
            [
                'title' => 'Reply to client about contract changes',
                'description' => 'Short response is enough; do not turn it into a project.',
                'status' => 'escalated',
                'priority' => 'urgent',
                'is_urgent' => true,
                'due_at' => now()->addHours(3),
                'deadline_type' => 'hard',
                'reminder_at' => now()->subHour(),
                'last_nudged_at' => now()->subHours(3),
                'nudge_count' => 4,
                'source' => 'message',
                'notes' => 'This is currently a Top 3 candidate.',
                'created_at' => now()->subDays(3),
            ],
            [
                'title' => 'Book dentist appointment',
                'status' => 'nudged',
                'priority' => 'important',
                'due_at' => now()->addDay(),
                'deadline_type' => 'soft',
                'reminder_at' => now()->subMinutes(30),
                'last_nudged_at' => now()->subHours(5),
                'nudge_count' => 2,
                'source' => 'thought',
                'created_at' => now()->subDays(9),
            ],
            [
                'title' => 'Send invoice for March work',
                'status' => 'active',
                'priority' => 'urgent',
                'is_urgent' => true,
                'due_at' => now()->subDay(),
                'deadline_type' => 'hard',
                'reminder_at' => now()->subDays(2),
                'source' => 'message',
                'created_at' => now()->subDays(4),
            ],
            [
                'title' => 'Check renewal date for insurance',
                'status' => 'active',
                'priority' => 'important',
                'due_at' => now()->addDays(4),
                'deadline_type' => 'hard',
                'reminder_at' => now()->addHours(8),
                'source' => 'other',
                'created_at' => now()->subDays(7),
            ],
            [
                'title' => 'Write outline for quarterly review',
                'status' => 'captured',
                'priority' => 'normal',
                'due_at' => now()->addDays(6),
                'deadline_type' => 'soft',
                'source' => 'thought',
                'created_at' => now()->subHours(18),
            ],
            [
                'title' => 'Return borrowed laptop charger',
                'status' => 'snoozed',
                'priority' => 'normal',
                'snoozed_until' => now()->addDay(),
                'source' => 'verbal',
                'created_at' => now()->subDays(2),
            ],
            [
                'title' => 'Ask Alex for budget numbers',
                'status' => 'blocked',
                'priority' => 'important',
                'blocked_reason' => 'Waiting for Alex to send the spreadsheet.',
                'source' => 'interruption',
                'created_at' => now()->subDays(5),
            ],
            [
                'title' => 'Clean up desktop downloads',
                'status' => 'captured',
                'priority' => 'normal',
                'reminder_at' => now()->addDays(2),
                'source' => 'thought',
                'created_at' => now()->subDays(1),
            ],
            [
                'title' => 'Review notes from planning call',
                'status' => 'active',
                'priority' => 'important',
                'reminder_at' => now()->addHours(4),
                'source' => 'verbal',
                'created_at' => now()->subDays(2),
            ],
            [
                'title' => 'Buy birthday card',
                'status' => 'active',
                'priority' => 'normal',
                'due_at' => now()->addDays(2),
                'deadline_type' => 'hard',
                'source' => 'thought',
                'created_at' => now()->subDays(6),
            ],
            [
                'title' => 'Decide on gym membership',
                'status' => 'snoozed',
                'priority' => 'normal',
                'snoozed_until' => now()->addDays(3),
                'source' => 'other',
                'created_at' => now()->subDays(10),
            ],
            [
                'title' => 'Finish expenses',
                'status' => 'completed',
                'priority' => 'important',
                'due_at' => now()->subDays(1),
                'source' => 'message',
                'created_at' => now()->subDays(8),
                'completed_at' => now()->subDays(1),
            ],
            [
                'title' => 'Archive old project folder',
                'status' => 'completed',
                'priority' => 'normal',
                'source' => 'other',
                'created_at' => now()->subDays(11),
                'completed_at' => now()->subDays(4),
            ],
            [
                'title' => 'Draft agenda for Monday meeting',
                'status' => 'completed',
                'priority' => 'important',
                'due_at' => now()->subDays(5),
                'source' => 'message',
                'created_at' => now()->subDays(9),
                'completed_at' => now()->subDays(6),
            ],
            [
                'title' => 'Order printer paper',
                'status' => 'completed',
                'priority' => 'normal',
                'source' => 'verbal',
                'created_at' => now()->subDays(4),
                'completed_at' => now()->subDays(2),
            ],
            [
                'title' => 'Check in on tax paperwork',
                'status' => 'nudged',
                'priority' => 'important',
                'reminder_at' => now()->subDay(),
                'last_nudged_at' => now()->subHours(8),
                'nudge_count' => 3,
                'source' => 'other',
                'created_at' => now()->subDays(14),
            ],
        ];

        foreach ($examples as $example) {
            $this->createExampleTask($user, $example);
        }
    }

    /**
     * Seeding is idempotent so repeated setup runs do not duplicate demo tasks.
     */
    private function createExampleTask(User $user, array $attributes): void
    {
        $exists = Task::query()
            ->where('user_id', $user->id)
            ->where('title', $attributes['title'])
            ->exists();

        if ($exists) {
            return;
        }

        $task = new Task;
        $task->fill([
            'user_id' => $user->id,
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? null,
            'status' => $attributes['status'] ?? 'captured',
            'priority' => $attributes['priority'] ?? 'normal',
            'is_urgent' => $attributes['is_urgent'] ?? false,
            'due_at' => $attributes['due_at'] ?? null,
            'deadline_type' => $attributes['deadline_type'] ?? 'none',
            'reminder_at' => $attributes['reminder_at'] ?? null,
            'last_nudged_at' => $attributes['last_nudged_at'] ?? null,
            'nudge_count' => $attributes['nudge_count'] ?? 0,
            'source' => $attributes['source'] ?? 'other',
            'notes' => $attributes['notes'] ?? null,
            'snoozed_until' => $attributes['snoozed_until'] ?? null,
            'blocked_reason' => $attributes['blocked_reason'] ?? null,
            'completed_at' => $attributes['completed_at'] ?? null,
        ]);

        $task->created_at = $attributes['created_at'] ?? now();
        $task->updated_at = $attributes['updated_at'] ?? $task->created_at;
        $task->save();
    }
}
