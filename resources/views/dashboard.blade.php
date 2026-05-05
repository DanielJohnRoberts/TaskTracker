@extends('layouts.app')

@section('content')
    <section class="dashboard-head">
        <div>
            <p class="eyebrow">Dashboard</p>
            <h1>Top 3 Tasks</h1>
        </div>
        <button class="secondary-button" id="enableNotifications" type="button">Enable notifications</button>
    </section>

    <form method="POST" action="{{ route('tasks.store') }}" class="quick-add">
        @csrf
        <div class="capture-row">
            <input type="text" name="title" value="{{ old('title') }}" placeholder="Capture a task..." required autocomplete="off">
            <button class="primary-button" type="submit">Add</button>
        </div>

        <details class="task-details">
            <summary>Details</summary>
            <div class="detail-grid">
                <label>
                    Description
                    <textarea name="description" rows="3">{{ old('description') }}</textarea>
                </label>
                <label>
                    Due
                    <input type="datetime-local" name="due_at" value="{{ old('due_at') }}">
                </label>
                <label>
                    Reminder
                    <input type="datetime-local" name="reminder_at" value="{{ old('reminder_at') }}">
                </label>
                <label>
                    Priority
                    <select name="priority">
                        @foreach (App\Models\Task::PRIORITIES as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Deadline
                    <select name="deadline_type">
                        @foreach (App\Models\Task::DEADLINE_TYPES as $deadlineType)
                            <option value="{{ $deadlineType }}" @selected(old('deadline_type', 'none') === $deadlineType)>{{ ucfirst(str_replace('_', ' ', $deadlineType)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Source
                    <select name="source">
                        @foreach (App\Models\Task::SOURCES as $source)
                            <option value="{{ $source }}" @selected(old('source', 'other') === $source)>{{ ucfirst($source) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="check-line">
                    <input type="checkbox" name="is_urgent" value="1" @checked(old('is_urgent'))>
                    Urgent
                </label>
                <label class="wide">
                    Notes
                    <textarea name="notes" rows="2">{{ old('notes') }}</textarea>
                </label>
            </div>
        </details>
    </form>

    <section class="stats-grid" aria-label="Task counts">
        <div class="stat"><span>{{ $counts['active'] }}</span><small>Active</small></div>
        <div class="stat"><span>{{ $counts['completed'] }}</span><small>Completed</small></div>
        <div class="stat"><span>{{ $counts['snoozed'] }}</span><small>Snoozed</small></div>
        <div class="stat danger"><span>{{ $counts['overdue'] }}</span><small>Overdue</small></div>
    </section>

    <section class="task-section">
        <div class="section-title">
            <h2>Top 3</h2>
        </div>
        <div class="task-list top-list">
            @forelse ($topTasks as $task)
                @include('tasks._card', ['task' => $task])
            @empty
                <p class="empty-state">No open tasks.</p>
            @endforelse
        </div>
    </section>

    <section class="dashboard-grid">
        <div class="task-section">
            <div class="section-title">
                <h2>Urgent</h2>
            </div>
            <div class="task-list">
                @forelse ($urgentTasks as $task)
                    @include('tasks._card', ['task' => $task])
                @empty
                    <p class="empty-state">No urgent tasks.</p>
                @endforelse
            </div>
        </div>

        <div class="task-section">
            <div class="section-title">
                <h2>Recently Added</h2>
            </div>
            <div class="task-list">
                @forelse ($recentTasks as $task)
                    @include('tasks._card', ['task' => $task])
                @empty
                    <p class="empty-state">No recent tasks.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
