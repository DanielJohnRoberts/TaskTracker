@extends('layouts.app')

@section('content')
    <section class="dashboard-head">
        <div>
            <p class="eyebrow">Reporting</p>
            <h1>Task Signals</h1>
        </div>
        <a class="button-link" href="{{ route('dashboard') }}">Dashboard</a>
    </section>

    <section class="report-metrics" aria-label="Task report metrics">
        @foreach ($metrics as $metric)
            <article class="metric-card tone-{{ $metric['tone'] }}">
                <span>{{ $metric['value'] }}</span>
                <small>{{ $metric['label'] }}</small>
            </article>
        @endforeach
    </section>

    <section class="report-grid">
        <article class="report-panel wide-panel">
            <div class="section-title">
                <h2>Completed This Week</h2>
            </div>
            <div class="trend-chart">
                @foreach ($completionTrend as $day)
                    <div class="trend-day">
                        <div class="trend-track">
                            <span class="trend-fill" style="--h: {{ max(8, $day['percent']) }}%"></span>
                        </div>
                        <strong>{{ $day['count'] }}</strong>
                        <small>{{ $day['label'] }}</small>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="report-panel">
            <div class="section-title">
                <h2>Status Mix</h2>
            </div>
            <div class="bar-stack">
                @foreach ($statusRows as $row)
                    @include('reports._bar-row', ['row' => $row])
                @endforeach
            </div>
        </article>

        <article class="report-panel">
            <div class="section-title">
                <h2>Priority</h2>
            </div>
            <div class="bar-stack">
                @foreach ($priorityRows as $row)
                    @include('reports._bar-row', ['row' => $row])
                @endforeach
            </div>
        </article>

        <article class="report-panel">
            <div class="section-title">
                <h2>Sources</h2>
            </div>
            <div class="bar-stack">
                @foreach ($sourceRows as $row)
                    @include('reports._bar-row', ['row' => $row])
                @endforeach
            </div>
        </article>

        <article class="report-panel">
            <div class="section-title">
                <h2>Deadline Pressure</h2>
            </div>
            <div class="compact-list">
                @forelse ($deadlineTasks as $task)
                    <div class="compact-item">
                        <strong>{{ $task->title }}</strong>
                        <span class="{{ $task->due_at->isPast() ? 'overdue-text' : '' }}">
                            {{ $task->due_at->format('M j, H:i') }}
                        </span>
                    </div>
                @empty
                    <p class="empty-state">No dated tasks.</p>
                @endforelse
            </div>
        </article>

        <article class="report-panel wide-panel">
            <div class="section-title">
                <h2>Most Nudged</h2>
            </div>
            <div class="nudge-list">
                @forelse ($nudgeHeavyTasks as $task)
                    <div class="nudge-item">
                        <span class="nudge-count">{{ $task->nudge_count }}</span>
                        <div>
                            <strong>{{ $task->title }}</strong>
                            <small>{{ ucfirst($task->status) }} · {{ ucfirst($task->priority) }}</small>
                        </div>
                    </div>
                @empty
                    <p class="empty-state">No nudges yet.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
