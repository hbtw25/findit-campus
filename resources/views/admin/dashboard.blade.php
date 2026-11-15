@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('admin_content')
<h1 class="page-title">Dashboard</h1>
<p class="page-sub">Ringkasan aktivitas FindIt Campus.</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat"><i class="bi bi-geo-alt"></i><div class="num mt-2">{{ $stats['active'] }}</div><div class="lbl">Laporan aktif</div></div>
    </div>
    <div class="col-6 col-xl-3">
        <a href="{{ route('admin.claims.index') }}" class="stat d-block text-body {{ $stats['pending'] ? 'attention' : '' }}"><i class="bi bi-hourglass-split"></i><div class="num mt-2">{{ $stats['pending'] }}</div><div class="lbl">Klaim menunggu verifikasi</div></a>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat"><i class="bi bi-bag-check"></i><div class="num mt-2">{{ $stats['resolved_month'] }}</div><div class="lbl">Barang kembali bulan ini</div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat"><i class="bi bi-people"></i><div class="num mt-2">{{ $stats['users'] }}</div><div class="lbl">Pengguna terdaftar</div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="section-card">
            <div class="section-title justify-content-between">
                <span><i class="bi bi-patch-check me-2"></i>Klaim terlama yang menunggu</span>
                <a href="{{ route('admin.claims.index') }}" class="small-caption">Semua klaim</a>
            </div>
            @forelse ($pendingClaims as $claim)
                <a href="{{ route('admin.claims.show', $claim) }}" class="list-row">
                    @if ($claim->report->thumb_url)
                        <img src="{{ $claim->report->thumb_url }}" class="thumb" alt="">
                    @else
                        <div class="thumb"><div class="photo-empty found"><i class="bi bi-box-seam"></i></div></div>
                    @endif
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-truncate">{{ $claim->report->title }}</div>
                        <div class="small-caption">oleh {{ $claim->user->name }} · {{ $claim->created_at->diffForHumans() }}</div>
                    </div>
                    <span class="btn btn-ink btn-sm">Tinjau</span>
                </a>
            @empty
                <div class="empty-state"><i class="bi bi-check2-all"></i>Tidak ada klaim yang menunggu.</div>
            @endforelse
        </div>
    </div>
    <div class="col-xl-5">
        <div class="section-card mb-4">
            <div class="section-title"><i class="bi bi-bar-chart"></i>Laporan aktif per kategori</div>
            @php($max = max(1, $byCategory->max('reports_count')))
            @foreach ($byCategory as $category)
                <div class="bar-row">
                    <span class="text-truncate"><i class="bi bi-{{ $category->icon }} me-1"></i>{{ $category->name }}</span>
                    <div class="bar"><span style="width: {{ $category->reports_count / $max * 100 }}%"></span></div>
                    <strong class="text-end">{{ $category->reports_count }}</strong>
                </div>
            @endforeach
        </div>
        <div class="section-card">
            <div class="section-title"><i class="bi bi-clock-history"></i>Laporan terbaru</div>
            @foreach ($latestReports as $report)
                <a href="{{ route('reports.show', $report) }}" class="list-row py-2">
                    @include('partials.type-label', ['type' => $report->type])
                    <div class="flex-grow-1 min-w-0 text-truncate">{{ $report->title }}</div>
                    <span class="small-caption text-nowrap">{{ $report->created_at->diffForHumans(null, true) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
