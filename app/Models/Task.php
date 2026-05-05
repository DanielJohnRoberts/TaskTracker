<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    public const STATUSES = [
        'captured',
        'active',
        'nudged',
        'escalated',
        'snoozed',
        'blocked',
        'completed',
    ];

    public const PRIORITIES = ['normal', 'important', 'urgent'];

    public const DEADLINE_TYPES = ['none', 'soft', 'hard'];

    public const SOURCES = ['message', 'verbal', 'thought', 'interruption', 'other'];

    public const RECURRENCE_TYPES = ['none', 'fixed', 'after_completion', 'reminder_only'];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'is_urgent',
        'due_at',
        'deadline_type',
        'reminder_at',
        'last_nudged_at',
        'nudge_count',
        'source',
        'notes',
        'snoozed_until',
        'blocked_reason',
        'recurrence_type',
        'recurrence_interval',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_urgent' => 'boolean',
            'due_at' => 'datetime',
            'reminder_at' => 'datetime',
            'last_nudged_at' => 'datetime',
            'snoozed_until' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDashboardEligible(Builder $query): Builder
    {
        // Main dashboard keeps hidden states out until the user or snooze timer brings them back.
        return $query
            ->whereNotIn('status', ['completed', 'blocked'])
            ->where(function (Builder $query): void {
                $query
                    ->where('status', '!=', 'snoozed')
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where('status', 'snoozed')
                            ->whereNotNull('snoozed_until')
                            ->where('snoozed_until', '<=', now());
                    });
            });
    }

    public function scopeRanked(Builder $query): Builder
    {
        // Top 3 ranking: urgent first, then deadline pressure, then longest ignored.
        return $query
            ->orderByDesc('is_urgent')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'important' THEN 1 ELSE 2 END")
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->orderByRaw('COALESCE(last_nudged_at, updated_at, created_at) ASC');
    }

    public static function releaseExpiredSnoozesFor(int $userId): void
    {
        self::query()
            ->forUser($userId)
            ->where('status', 'snoozed')
            ->whereNotNull('snoozed_until')
            ->where('snoozed_until', '<=', now())
            ->update([
                'status' => 'active',
                'snoozed_until' => null,
            ]);
    }
}
