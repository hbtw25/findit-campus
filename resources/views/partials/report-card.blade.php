<a href="{{ route('reports.show', $report) }}" class="report-card">
    <div class="rc-photo">
        @if ($report->thumb_url)
            <img src="{{ $report->photo_url }}" alt="{{ $report->title }}" loading="lazy">
        @else
            <div class="photo-empty {{ $report->type }}"><i class="bi bi-{{ $report->category->icon }}"></i></div>
        @endif
        @include('partials.type-label', ['type' => $report->type])
    </div>
    <div class="rc-body">
        <div class="rc-title">{{ $report->title }}</div>
        <div class="rc-meta">{{ $report->event_at->diffForHumans() }} · {{ $report->campusLocation?->short_name ?? $report->place_name }}</div>
        @if ($report->status === 'in_claim')
            <span class="status-badge s-in_claim mt-2">Proses klaim</span>
        @endif
    </div>
</a>
