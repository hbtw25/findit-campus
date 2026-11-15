@extends('layouts.app')

@section('title', $report->title)

@section('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endsection

@section('content')
<nav class="mb-3 small"><a href="{{ url()->previous() === url()->current() ? route('map') : url()->previous() }}" class="text-muted"><i class="bi bi-arrow-left me-1"></i>Kembali</a></nav>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="detail-photo {{ $report->photo_url ? '' : 'is-empty' }}">
            @if ($report->photo_url)
                <img src="{{ $report->photo_url }}" alt="{{ $report->title }}">
            @else
                <div class="photo-empty {{ $report->type }}"><i class="bi bi-{{ $report->category->icon }}"></i></div>
            @endif
        </div>

        <div class="section-card mt-3">
            <div class="section-title"><i class="bi bi-geo-alt"></i>Lokasi {{ $report->isLost() ? 'terakhir terlihat' : 'ditemukan' }}</div>
            <div class="mini-map" id="mini-map"></div>
            <div class="small-caption mt-2">{{ $report->place_name }}@if ($report->location_note) · {{ $report->location_note }}@endif</div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="section-card">
            <div class="d-flex gap-2 flex-wrap mb-2">
                @include('partials.type-label', ['type' => $report->type])
                <span class="status-badge s-{{ $report->status }}">{{ $report->status_label }}</span>
                @if ($report->is_hidden)<span class="status-badge s-rejected">Disembunyikan admin</span>@endif
            </div>
            <h1 class="h3 mb-3">{{ $report->title }}</h1>

            <div class="meta-list">
                <div class="meta-item"><i class="bi bi-{{ $report->category->icon }}"></i><div><span class="label">Kategori</span>{{ $report->category->name }}</div></div>
                <div class="meta-item"><i class="bi bi-clock"></i><div><span class="label">{{ $report->isLost() ? 'Waktu hilang' : 'Waktu ditemukan' }}</span>{{ $report->event_at->translatedFormat('l, d F Y · H:i') }} ({{ $report->event_at->diffForHumans() }})</div></div>
                <div class="meta-item"><i class="bi bi-building"></i><div><span class="label">Lokasi</span>{{ $report->place_name }}</div></div>
                @if ($report->color || $report->brand)
                    <div class="meta-item"><i class="bi bi-palette"></i><div><span class="label">Warna / merek</span>{{ collect([$report->color, $report->brand])->filter()->implode(' · ') }}</div></div>
                @endif
            </div>

            <p class="mb-3" style="white-space: pre-line">{{ $report->description }}</p>

            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="avatar avatar-sm">{{ $report->user->initials }}</span>
                <span class="small">Dilaporkan oleh <strong>{{ $report->user->short_name }}</strong> · {{ $report->created_at->diffForHumans() }}</span>
            </div>

            {{-- Aksi utama sesuai peran --}}
            <div class="d-grid gap-2">
                @if ($isOwner)
                    @if ($report->status === 'open')
                        <a href="{{ route('reports.edit', $report) }}" class="btn btn-outline-ink"><i class="bi bi-pencil me-1"></i>Ubah laporan</a>
                    @endif
                    @if ($report->isLost() && $report->isOpen())
                        <form method="POST" action="{{ route('reports.resolve', $report) }}" data-confirm="Tandai barang ini sudah ketemu?">
                            @csrf
                            <button class="btn btn-found w-100"><i class="bi bi-check2-circle me-1"></i>Barang sudah ketemu</button>
                        </form>
                    @endif
                @elseif ($report->isFound() && $report->isOpen())
                    @if ($myClaim && $myClaim->isActive())
                        <a href="{{ route('claims.show', $myClaim) }}" class="btn btn-ink">Lihat klaim saya ({{ $myClaim->status_label }})</a>
                    @elseif ($hasApprovedClaim)
                        <div class="secret-note"><i class="bi bi-hourglass-split"></i><span>Barang ini sedang dalam proses serah terima dengan pengklaim yang sudah diverifikasi admin.</span></div>
                    @elseif ($myClaim && $myClaim->status === 'rejected')
                        <div class="secret-note"><i class="bi bi-x-circle"></i><span>Klaim kamu untuk barang ini sudah ditolak. Hubungi admin bila ada kekeliruan.</span></div>
                    @else
                        <a href="{{ route('claims.create', $report) }}" class="btn btn-brand py-2"><i class="bi bi-patch-check me-1"></i>Ajukan Klaim</a>
                    @endif
                @elseif ($report->isLost() && $report->isOpen())
                    <a href="{{ route('reports.create', ['type' => 'found', 'category' => $report->category_id, 'location' => $report->campus_location_id]) }}" class="btn btn-found py-2">
                        <i class="bi bi-hand-index-thumb me-1"></i>Saya menemukan barang ini
                    </a>
                @endif
                <button type="button" class="btn btn-outline-ink" data-bs-toggle="modal" data-bs-target="#share-modal"><i class="bi bi-share me-1"></i>Bagikan</button>
            </div>

            @if ($report->isFound())
                <div class="secret-note mt-3"><i class="bi bi-shield-lock"></i><span>Ciri rahasia barang ini hanya diketahui penemu dan admin, dan dipakai untuk memverifikasi klaim.</span></div>
            @endif

            @if ($isOwner && $report->isOpen())
                <div class="d-flex gap-3 mt-3 small">
                    <form method="POST" action="{{ route('reports.close', $report) }}" data-confirm="Tutup laporan ini? Laporan tidak akan tampil lagi di peta.">
                        @csrf
                        <button class="btn btn-link p-0 text-muted small">Tutup laporan</button>
                    </form>
                    @if ($incomingClaims->isEmpty())
                        <form method="POST" action="{{ route('reports.destroy', $report) }}" data-confirm="Hapus laporan ini secara permanen?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link p-0 text-danger small">Hapus</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        {{-- Khusus pemilik: ciri rahasia dan klaim masuk --}}
        @if ($isOwner && $report->isFound())
            <div class="section-card">
                <div class="section-title"><i class="bi bi-shield-lock"></i>Ciri rahasia (hanya kamu & admin)</div>
                <p class="mb-0">{{ $report->secret_detail }}</p>
            </div>
            <div class="section-card">
                <div class="section-title"><i class="bi bi-inbox"></i>Klaim masuk</div>
                @forelse ($incomingClaims as $claim)
                    <a href="{{ route('claims.show', $claim) }}" class="list-row">
                        <span class="avatar avatar-sm">{{ $claim->user->initials }}</span>
                        <div class="flex-grow-1">{{ $claim->user->short_name }} <div class="small-caption">{{ $claim->created_at->diffForHumans() }}</div></div>
                        <span class="status-badge s-{{ $claim->status }}">{{ $claim->status_label }}</span>
                    </a>
                @empty
                    <p class="text-muted mb-0 small">Belum ada klaim.</p>
                @endforelse
            </div>
        @endif
    </div>
</div>

{{-- Kandidat auto-match untuk pemilik laporan --}}
@if ($isOwner && $report->isOpen())
    <div class="section-card mt-4" id="kandidat">
        <div class="section-title"><i class="bi bi-binoculars"></i>{{ $report->isLost() ? 'Barang temuan yang diduga milikmu' : 'Laporan hilang yang mirip dengan temuanmu' }}</div>
        @forelse ($matches as $match)
            @php($other = $report->isLost() ? $match->foundReport : $match->lostReport)
            <div class="match-card">
                <a href="{{ route('reports.show', $other) }}" class="thumb thumb-lg">
                    @if ($other->thumb_url)
                        <img src="{{ $other->thumb_url }}" class="thumb thumb-lg" alt="">
                    @else
                        <div class="photo-empty {{ $other->type }}"><i class="bi bi-{{ $report->category->icon }}"></i></div>
                    @endif
                </a>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between gap-2">
                        <a href="{{ route('reports.show', $other) }}" class="fw-bold">{{ $other->title }}</a>
                        <span class="score-pill">{{ $match->score }} <small>{{ $match->source_label }}</small></span>
                    </div>
                    <div class="score-bar {{ $match->score < 70 ? 'mid' : '' }}"><span style="width: {{ $match->score }}%"></span></div>
                    <div class="small-caption mb-1">{{ $other->event_at->diffForHumans() }} · {{ $other->place_name }} · {{ $match->distance_label }} dari lokasimu</div>
                    @if ($match->ai_reason)
                        <div class="match-reason">{{ $match->ai_reason }}</div>
                    @endif
                    <div class="d-flex gap-2 mt-2">
                        @if ($report->isLost())
                            <a href="{{ route('claims.create', ['report' => $other, 'lost' => $report->id]) }}" class="btn btn-brand btn-sm">Ajukan klaim</a>
                        @endif
                        <a href="{{ route('reports.show', $other) }}" class="btn btn-outline-ink btn-sm">Lihat</a>
                        @if ($report->isLost())
                            <form method="POST" action="{{ route('matches.dismiss', $match) }}">
                                @csrf
                                <button class="btn btn-link btn-sm text-muted">Bukan barang saya</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state py-3">
                <i class="bi bi-hourglass-split"></i>
                Belum ada yang cocok. Sistem otomatis mencocokkan setiap ada laporan baru, dan kamu akan dapat notifikasi.
            </div>
        @endforelse
    </div>
@endif

@include('reports.partials.share-modal', ['report' => $report])
@endsection

@section('scripts')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('vendor/qrcodejs/qrcode.min.js') }}"></script>
    <script>
        window.FINDIT_REPORT = {
            lat: {{ $report->lat }},
            lng: {{ $report->lng }},
            type: "{{ $report->type }}",
            thumb: @json($report->thumb_url),
            icon: "{{ $report->category->icon }}",
            shareUrl: "{{ $report->share_url }}",
        };
    </script>
    <script src="{{ asset('js/report-show.js') }}"></script>
@endsection
