<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login_before_seeing_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_public_registration_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_a_user_can_log_in_and_capture_a_title_only_task(): void
    {
        $user = User::factory()->create([
            'email' => 'daniel@example.com',
            'password_hash' => bcrypt('password123'),
        ]);

        $this->post('/login', [
            'email' => 'daniel@example.com',
            'password' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->actingAs($user)
            ->post('/tasks', [
                'title' => 'Send invoice',
            ])->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Send invoice',
            'status' => 'captured',
        ]);

        $this->get('/dashboard')->assertSee('Send invoice');
    }

    public function test_top_three_ranking_prefers_urgent_then_due_soon(): void
    {
        $user = User::factory()->create();

        Task::query()->create([
            'user_id' => $user->id,
            'title' => 'Later normal task',
            'due_at' => now()->addWeek(),
        ]);

        Task::query()->create([
            'user_id' => $user->id,
            'title' => 'Soon normal task',
            'due_at' => now()->addHour(),
        ]);

        Task::query()->create([
            'user_id' => $user->id,
            'title' => 'Urgent task',
            'is_urgent' => true,
            'priority' => 'urgent',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSeeInOrder(['Urgent task', 'Soon normal task', 'Later normal task']);
    }

    public function test_completed_tasks_leave_the_dashboard(): void
    {
        $user = User::factory()->create();
        $task = Task::query()->create([
            'user_id' => $user->id,
            'title' => 'Invisible after completion',
        ]);

        $this->actingAs($user)
            ->patch(route('tasks.complete', $task))
            ->assertRedirect();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertDontSee('Invisible after completion');
    }

    public function test_reminder_endpoint_nudges_due_tasks_and_rate_limits_repeats(): void
    {
        $user = User::factory()->create();

        Task::query()->create([
            'user_id' => $user->id,
            'title' => 'Nudge me',
            'reminder_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->getJson(route('api.reminders'))
            ->assertOk()
            ->assertJsonPath('tasks.0.title', 'Nudge me');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Nudge me',
            'status' => 'nudged',
            'nudge_count' => 1,
        ]);

        $this->actingAs($user)
            ->getJson(route('api.reminders'))
            ->assertOk()
            ->assertJsonCount(0, 'tasks');
    }

    public function test_admin_can_create_a_basic_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'username' => 'newuser',
                'email' => 'newuser@example.com',
                'role' => 'user',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'role' => 'user',
        ]);
    }

    public function test_admin_can_edit_a_users_role(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $user = User::factory()->create([
            'username' => 'teamuser',
            'email' => 'teamuser@example.com',
            'role' => 'user',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'username' => 'teamuser',
                'email' => 'teamuser@example.com',
                'role' => 'admin',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'admin',
        ]);
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->create([
            'username' => 'onlyadmin',
            'email' => 'onlyadmin@example.com',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'username' => 'onlyadmin',
                'email' => 'onlyadmin@example.com',
                'role' => 'user',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.edit', $admin));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);
    }

    public function test_basic_users_cannot_access_user_admin(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_reports_show_current_users_task_signals_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Task::query()->create([
            'user_id' => $user->id,
            'title' => 'Visible deadline task',
            'due_at' => now()->addHour(),
            'priority' => 'urgent',
            'is_urgent' => true,
        ]);

        Task::query()->create([
            'user_id' => $otherUser->id,
            'title' => 'Other private task',
            'due_at' => now()->addHour(),
            'priority' => 'urgent',
            'is_urgent' => true,
        ]);

        $this->actingAs($user)
            ->get(route('reports'))
            ->assertOk()
            ->assertSee('Task Signals')
            ->assertSee('Visible deadline task')
            ->assertDontSee('Other private task');
    }

    public function test_database_seeder_creates_demo_users_and_example_tasks(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'role' => 'user',
        ]);

        $basicUser = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->assertGreaterThanOrEqual(12, $basicUser->tasks()->count());
        $this->assertDatabaseHas('tasks', [
            'user_id' => $basicUser->id,
            'title' => 'Reply to client about contract changes',
            'status' => 'escalated',
        ]);
    }

    public function test_user_can_save_a_push_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.push.store'), [
                'endpoint' => 'https://updates.push.services.mozilla.com/wpush/v2/example',
                'keys' => [
                    'p256dh' => 'public-key-example',
                    'auth' => 'auth-token-example',
                ],
                'contentEncoding' => 'aes128gcm',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://updates.push.services.mozilla.com/wpush/v2/example',
            'content_encoding' => 'aes128gcm',
        ]);
    }

    public function test_push_command_dry_run_finds_due_tasks_without_marking_them(): void
    {
        $user = User::factory()->create();

        $user->pushSubscriptions()->create([
            'endpoint' => 'https://updates.push.services.mozilla.com/wpush/v2/example',
            'public_key' => 'public-key-example',
            'auth_token' => 'auth-token-example',
            'content_encoding' => 'aes128gcm',
        ]);

        $task = Task::query()->create([
            'user_id' => $user->id,
            'title' => 'Push dry run task',
            'reminder_at' => now()->subMinute(),
        ]);

        $this->artisan('tasks:send-push', ['--dry-run' => true])
            ->expectsOutput('Due tasks: 1; attempted pushes: 0; accepted pushes: 0; expired subscriptions: 0.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'captured',
            'nudge_count' => 0,
        ]);
    }
}
