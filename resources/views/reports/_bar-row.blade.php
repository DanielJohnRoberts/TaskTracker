<div class="bar-row">
    <div class="bar-label">
        <span>{{ ucfirst(str_replace('_', ' ', $row['label'])) }}</span>
        <strong>{{ $row['count'] }}</strong>
    </div>
    <div class="bar-track" aria-hidden="true">
        <span class="bar-fill" style="--w: {{ $row['percent'] }}%"></span>
    </div>
</div>
