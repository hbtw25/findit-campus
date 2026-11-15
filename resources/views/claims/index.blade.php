@extends('layouts.app')

@section('title', 'Klaim')

@section('content')
<h1 class="page-title">Klaim kepemilikan</h1>
<p class="page-sub">Klaim yang kamu ajukan dan klaim masuk untuk barang temuanmu.</p>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="section-card h-100">
            <div class="section-title"><i class="bi bi-send"></i>Klaim saya</div>
            @forelse ($sent as $claim)
                <a href="{{ route('claims.show', $claim) }}" class="list-row">
                    @if ($claim->report->thumb_url)
                        <img src="{{ $claim->report->thumb_url }}" class="thumb" alt="">
                    @else
                        <div class="thumb"><div class="photo-empty found"><i class="bi bi-box-seam"></i></div></div>
                    @endif
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-truncate">{{ $claim->report->title }}</div>
                        <div class="small-caption">Diajukan {{ $claim->created_at->diffForHumans() }}</div>
                    </div>
                    <span class="status-badge s-{{ $claim->status }}">{{ $claim->status_label }}</span>
                </a>
            @empty
                <div class="empty-state"><i class="bi bi-patch-question"></i>Belum ada klaim. Temukan barangmu di <a href="{{ route('map') }}" class="link-underline">Peta Global</a>.</div>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="section-card h-100">
            <div class="section-title"><i class="bi bi-inbox"></i>Klaim untuk barang temuanku</div>
            @forelse ($incoming as $claim)
                <a href="{{ route('claims.show', $claim) }}" class="list-row">
                    <span class="avatar avatar-sm">{{ $claim->user->initials }}</span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-truncate">{{ $claim->report->title }}</div>
                        <div class="small-caption">oleh {{ $claim->user->short_name }} · {{ $claim->created_at->diffForHumans() }}</div>
                    </div>
                    <span class="status-badge s-{{ $claim->status }}">{{ $claim->status_label }}</span>
                </a>
            @empty
                <div class="empty-state"><i class="bi bi-inbox"></i>Belum ada klaim masuk.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
