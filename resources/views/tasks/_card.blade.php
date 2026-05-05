<article class="task-card {{ $task->status === 'escalated' ? 'is-escalated' : '' }}">
    <div class="task-main">
        <div>
            <h3>{{ $task->title }}</h3>
            <div class="task-meta">
                <span class="badge status-{{ $task->status }}">{{ ucfirst($task->status) }}</span>
                <span class="badge priority-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span>
                @if ($task->is_urgent)
                    <span class="badge urgent-badge">Urgent</span>
                @endif
                @if ($task->due_at)
                    <span class="due {{ $task->due_at->isPast() ? 'overdue' : '' }}">
                        Due {{ $task->due_at->format('M j, H:i') }}
                    </span>
                @endif
                @if ($task->snoozed_until)
                    <span class="due">Until {{ $task->snoozed_until->format('M j, H:i') }}</span>
                @endif
            </div>
        </div>
    </div>

    @if ($task->description || $task->notes || $task->blocked_reason)
        <div class="task-copy">
            @if ($task->description)
                <p>{{ $task->description }}</p>
            @endif
            @if ($task->notes)
                <p>{{ $task->notes }}</p>
            @endif
            @if ($task->blocked_reason)
                <p>{{ $task->blocked_reason }}</p>
            @endif
        </div>
    @endif

    <div class="task-actions">
        @if ($task->status !== 'completed')
            <form method="POST" action="{{ route('tasks.complete', $task) }}">
                @csrf
                @method('PATCH')
                <button type="submit">Complete</button>
            </form>
        @endif

        <form method="POST" action="{{ route('tasks.urgent', $task) }}">
            @csrf
            @method('PATCH')
            <button type="submit">{{ $task->is_urgent ? 'Unurgent' : 'Urgent' }}</button>
        </form>

        @if ($task->status !== 'blocked')
            <form method="POST" action="{{ route('tasks.snooze', $task) }}" class="inline-form">
                @csrf
                @method('PATCH')
                <select name="minutes" aria-label="Snooze duration">
                    <option value="60">1h</option>
                    <option value="240">4h</option>
                    <option value="1440">1d</option>
                    <option value="4320">3d</option>
                </select>
                <button type="submit">Snooze</button>
            </form>
        @endif

        <a class="button-link" href="{{ route('tasks.edit', $task) }}">Edit</a>

        @if ($task->status === 'blocked')
            <form method="POST" action="{{ route('tasks.unblock', $task) }}">
                @csrf
                @method('PATCH')
                <button type="submit">Unblock</button>
            </form>
        @else
            <form method="POST" action="{{ route('tasks.block', $task) }}">
                @csrf
                @method('PATCH')
                <button type="submit">Block</button>
            </form>
        @endif

        <form method="POST" action="{{ route('tasks.destroy', $task) }}" data-confirm="Delete this task?">
            @csrf
            @method('DELETE')
            <button class="danger-button" type="submit">Delete</button>
        </form>
    </div>
</article>
