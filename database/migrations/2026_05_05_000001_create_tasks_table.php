<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['captured', 'active', 'nudged', 'escalated', 'snoozed', 'blocked', 'completed'])->default('captured');
            $table->enum('priority', ['normal', 'important', 'urgent'])->default('normal');
            $table->boolean('is_urgent')->default(false);
            $table->dateTime('due_at')->nullable();
            $table->enum('deadline_type', ['none', 'soft', 'hard'])->default('none');
            $table->dateTime('reminder_at')->nullable();
            $table->dateTime('last_nudged_at')->nullable();
            $table->unsignedInteger('nudge_count')->default(0);
            $table->enum('source', ['message', 'verbal', 'thought', 'interruption', 'other'])->default('other');
            $table->text('notes')->nullable();
            $table->dateTime('snoozed_until')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->enum('recurrence_type', ['none', 'fixed', 'after_completion', 'reminder_only'])->default('none');
            $table->string('recurrence_interval', 50)->nullable();
            $table->timestamps();
            $table->dateTime('completed_at')->nullable();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'due_at']);
            $table->index(['user_id', 'reminder_at']);
            $table->index(['user_id', 'snoozed_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
