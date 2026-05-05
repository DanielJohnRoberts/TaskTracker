@extends('layouts.app')

@section('content')
    <section class="edit-head">
        <div>
            <p class="eyebrow">Edit</p>
            <h1>{{ $task->title }}</h1>
        </div>
        <a class="button-link" href="{{ route('dashboard') }}">Dashboard</a>
    </section>

    <form method="POST" action="{{ route('tasks.update', $task) }}" class="edit-form">
        @csrf
        @method('PUT')

        <label>
            Title
            <input type="text" name="title" value="{{ old('title', $task->title) }}" required autofocus>
        </label>

        <label>
            Description
            <textarea name="description" rows="4">{{ old('description', $task->description) }}</textarea>
        </label>

        <div class="detail-grid">
            <label>
                Status
                <select name="status">
                    @foreach (App\Models\Task::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('status', $task->status) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Priority
                <select name="priority">
                    @foreach (App\Models\Task::PRIORITIES as $priority)
                        <option value="{{ $priority }}" @selected(old('priority', $task->priority) === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Due
                <input type="datetime-local" name="due_at" value="{{ old('due_at', optional($task->due_at)->format('Y-m-d\TH:i')) }}">
            </label>
            <label>
                Deadline
                <select name="deadline_type">
                    @foreach (App\Models\Task::DEADLINE_TYPES as $deadlineType)
                        <option value="{{ $deadlineType }}" @selected(old('deadline_type', $task->deadline_type) === $deadlineType)>{{ ucfirst(str_replace('_', ' ', $deadlineType)) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Reminder
                <input type="datetime-local" name="reminder_at" value="{{ old('reminder_at', optional($task->reminder_at)->format('Y-m-d\TH:i')) }}">
            </label>
            <label>
                Source
                <select name="source">
                    @foreach (App\Models\Task::SOURCES as $source)
                        <option value="{{ $source }}" @selected(old('source', $task->source) === $source)>{{ ucfirst($source) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Snoozed until
                <input type="datetime-local" name="snoozed_until" value="{{ old('snoozed_until', optional($task->snoozed_until)->format('Y-m-d\TH:i')) }}">
            </label>
            <label>
                Recurrence
                <select name="recurrence_type">
                    @foreach (App\Models\Task::RECURRENCE_TYPES as $recurrenceType)
                        <option value="{{ $recurrenceType }}" @selected(old('recurrence_type', $task->recurrence_type) === $recurrenceType)>{{ ucfirst(str_replace('_', ' ', $recurrenceType)) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Interval
                <input type="text" name="recurrence_interval" value="{{ old('recurrence_interval', $task->recurrence_interval) }}" placeholder="daily, weekly, 3 days">
            </label>
            <label class="check-line">
                <input type="checkbox" name="is_urgent" value="1" @checked(old('is_urgent', $task->is_urgent))>
                Urgent
            </label>
        </div>

        <label>
            Notes
            <textarea name="notes" rows="4">{{ old('notes', $task->notes) }}</textarea>
        </label>

        <label>
            Blocked reason
            <textarea name="blocked_reason" rows="3">{{ old('blocked_reason', $task->blocked_reason) }}</textarea>
        </label>

        <div class="form-actions">
            <button class="primary-button" type="submit">Save</button>
            <a class="button-link" href="{{ route('dashboard') }}">Cancel</a>
        </div>
    </form>
@endsection
