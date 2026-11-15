@extends('layouts.app')

@section('title', 'Laporan Saya')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <h1 class="page-title">Laporan saya</h1>
        <p class="page-sub mb-0">Pantau laporanmu dan kandidat barang yang cocok.</p>
    </div>
    <a href="{{ route('reports.create') }}" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Laporkan</a>
</div>

<ul class="nav nav-pills gap-2 mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="btn btn-sm btn-ink active" data-bs-toggle="pill" data-bs-target="#tab-active" type="button" role="tab">Aktif ({{ $active->count() }})</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="btn btn-sm btn-outline-ink" data-bs-toggle="pill" data-bs-target="#tab-history" type="button" role="tab">Riwayat ({{ $history->count() }})</button>
    </li>
</ul>

<div class="tab-content">
    @foreach (['tab-active' => $active, 'tab-history' => $history] as $tab => $items)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tab }}" role="tabpanel">
            <div class="section-card">
                @forelse ($items as $report)
                    <a href="{{ route('reports.show', $report) }}" class="list-row">
                        @if ($report->thumb_url)
                            <img src="{{ $report->thumb_url }}" class="thumb" alt="">
                        @else
                            <div class="thumb"><div class="photo-empty {{ $report->type }}"><i class="bi bi-{{ $report->category->icon }}"></i></div></div>
                        @endif
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex gap-2 flex-wrap">
                                @include('partials.type-label', ['type' => $report->type])
                                <span class="status-badge s-{{ $report->status }}">{{ $report->status_label }}</span>
                            </div>
                            <div class="fw-bold mt-1 text-truncate">{{ $report->title }}</div>
                            <div class="small-caption">{{ $report->event_at->diffForHumans() }} · {{ $report->place_name }}</div>
                        </div>
                        <div class="text-end small">
                            @php($candidates = $report->isLost() ? $report->lost_candidates : $report->found_candidates)
                            @if ($candidates)
                                <span class="type-label found"><i class="bi bi-binoculars"></i> {{ $candidates }} kandidat</span>
                            @endif
                            @if ($report->pending_claims)
                                <div class="mt-1"><span class="status-badge s-pending">{{ $report->pending_claims }} klaim menunggu</span></div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        {{ $tab === 'tab-active' ? 'Belum ada laporan aktif.' : 'Belum ada riwayat laporan.' }}
                        @if ($tab === 'tab-active')
                            <div class="mt-3"><a href="{{ route('reports.create') }}" class="btn btn-brand btn-sm">Buat laporan pertama</a></div>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script>
    // Ganti gaya tombol tab yang aktif
    $('[data-bs-toggle="pill"]').on('shown.bs.tab', function () {
        $('[data-bs-toggle="pill"]').removeClass('btn-ink active').addClass('btn-outline-ink');
        $(this).removeClass('btn-outline-ink').addClass('btn-ink active');
    });
</script>
@endsection
