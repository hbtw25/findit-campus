@extends('layouts.admin')

@section('title', 'Tinjau Klaim #' . $claim->id)

@section('admin_content')
<nav class="mb-3 small"><a href="{{ route('admin.claims.index') }}" class="text-muted"><i class="bi bi-arrow-left me-1"></i>Daftar klaim</a></nav>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h1 class="page-title">Klaim #{{ $claim->id }}</h1>
        <span class="status-badge s-{{ $claim->status }}">{{ $claim->status_label }}</span>
        <span class="small-caption ms-2">Diajukan {{ $claim->created_at->translatedFormat('d F Y, H:i') }}</span>
    </div>
    <a href="{{ route('claims.show', $claim) }}" class="btn btn-outline-ink btn-sm"><i class="bi bi-chat-dots me-1"></i>Halaman klaim & chat</a>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="section-card">
            <div class="section-title"><i class="bi bi-arrow-left-right"></i>Bandingkan ciri</div>
            <div class="compare">
                <div class="side secret">
                    <h6><i class="bi bi-shield-lock me-1"></i>Ciri rahasia dari penemu</h6>
                    <p class="mb-0 fw-semibold">{{ $claim->report->secret_detail ?: '-' }}</p>
                </div>
                <div class="side">
                    <h6><i class="bi bi-person me-1"></i>Jawaban pengklaim</h6>
                    <p class="mb-0 fw-semibold">{{ $claim->secret_answer }}</p>
                </div>
            </div>
            <div class="mt-3">
                <div class="small-caption fw-bold">Bukti kepemilikan</div>
                <p class="mb-0">{{ $claim->proof }}</p>
            </div>
            @if ($claim->lostReport)
                <div class="mt-3">
                    <div class="small-caption fw-bold">Laporan hilang yang ditautkan</div>
                    <a href="{{ route('reports.show', $claim->lostReport) }}" class="link-underline">{{ $claim->lostReport->title }}</a>
                    <span class="small-caption">· {{ $claim->lostReport->event_at->translatedFormat('d M Y H:i') }} · {{ $claim->lostReport->place_name }}</span>
                </div>
            @endif
        </div>

        @if ($claim->status === 'pending')
            <div class="section-card">
                <div class="section-title"><i class="bi bi-check2-square"></i>Keputusan</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('admin.claims.approve', $claim) }}" data-confirm="Setujui klaim ini? Klaim lain yang menunggu untuk barang ini akan otomatis ditolak.">
                            @csrf
                            <label for="approve-note" class="form-label">Catatan (opsional)</label>
                            <input type="text" name="admin_note" id="approve-note" class="form-control mb-2" maxlength="255" placeholder="Contoh: jawaban sesuai">
                            <button class="btn btn-found w-100"><i class="bi bi-check-lg me-1"></i>Setujui</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('admin.claims.reject', $claim) }}">
                            @csrf
                            <label for="reject-note" class="form-label required">Alasan penolakan</label>
                            <input type="text" name="admin_note" id="reject-note" class="form-control mb-2 @error('admin_note') is-invalid @enderror" maxlength="255"
                                   value="{{ old('admin_note') }}" placeholder="Minimal 10 karakter">
                            @error('admin_note')
                                <div class="invalid-feedback mb-2">{{ $message }}</div>
                            @enderror
                            <button class="btn btn-outline-danger w-100"><i class="bi bi-x-lg me-1"></i>Tolak</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="section-card">
                <div class="section-title"><i class="bi bi-clipboard-check"></i>Keputusan</div>
                <p class="mb-1">{{ $claim->status_label }} oleh {{ $claim->reviewer?->name ?? 'sistem' }} {{ $claim->reviewed_at?->diffForHumans() }}.</p>
                @if ($claim->admin_note)<p class="mb-0 text-muted">Catatan: {{ $claim->admin_note }}</p>@endif
                @if ($claim->status === 'approved')
                    <form method="POST" action="{{ route('claims.returned', $claim) }}" class="mt-3" data-confirm="Konfirmasi barang sudah diserahkan?">
                        @csrf
                        <button class="btn btn-found btn-sm"><i class="bi bi-bag-check me-1"></i>Konfirmasi Diserahkan</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    <div class="col-xl-4">
        <div class="section-card">
            <div class="section-title"><i class="bi bi-box-seam"></i>Barang temuan</div>
            @if ($claim->report->photo_url)
                <img src="{{ $claim->report->photo_url }}" class="w-100 rounded-3 mb-2" style="aspect-ratio:4/3;object-fit:cover" alt="">
            @endif
            <a href="{{ route('reports.show', $claim->report) }}" class="fw-bold">{{ $claim->report->title }}</a>
            <div class="small-caption">{{ $claim->report->category->name }} · {{ $claim->report->place_name }}</div>
            <div class="small-caption">Penemu: {{ $claim->report->user->name }}</div>
        </div>
        <div class="section-card">
            <div class="section-title"><i class="bi bi-person-badge"></i>Pengklaim</div>
            <div class="d-flex gap-2 align-items-center mb-2">
                <span class="avatar">{{ $claim->user->initials }}</span>
                <div><div class="fw-bold">{{ $claim->user->name }}</div><div class="small-caption">{{ $claim->user->nim }} · {{ $claim->user->email }}</div></div>
            </div>
            <div class="small-caption fw-bold mt-2">Riwayat klaim lain</div>
            @forelse ($history as $status => $total)
                <span class="status-badge s-{{ $status }} me-1">{{ \App\Models\Claim::STATUSES[$status] ?? $status }}: {{ $total }}</span>
            @empty
                <span class="small-caption">Belum pernah mengklaim sebelumnya.</span>
            @endforelse
        </div>
    </div>
</div>
@endsection
