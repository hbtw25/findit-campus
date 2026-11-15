@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h1 class="page-title mb-0">Notifikasi</h1>
            @if ($alerts->total())
                <form method="POST" action="{{ route('alerts.readAll') }}">
                    @csrf
                    <button class="btn btn-link btn-sm text-muted p-0">Tandai semua dibaca</button>
                </form>
            @endif
        </div>

        {{-- Kandidat cocok per laporan hilang (hasil auto-match) --}}
        @foreach ($candidates as $lostId => $group)
            @php($lost = $group->first()->lostReport)
            <div class="section-card mb-3" style="border-color: #BFE3D2">
                <div class="section-title mb-1"><i class="bi bi-binoculars" style="color: var(--found)"></i>Ada barang temuan yang diduga milikmu</div>
                <p class="small-caption mb-3">Untuk laporan hilang: <a href="{{ route('reports.show', $lost) }}" class="fw-bold text-body">{{ $lost->title }}</a></p>
                @foreach ($group->take(3) as $match)
                    @php($found = $match->foundReport)
                    <div class="match-card">
                        <a href="{{ route('reports.show', $found) }}" class="thumb thumb-lg">
                            @if ($found->thumb_url)
                                <img src="{{ $found->thumb_url }}" class="thumb thumb-lg" alt="">
                            @else
                                <div class="photo-empty found"><i class="bi bi-box-seam"></i></div>
                            @endif
                        </a>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('reports.show', $found) }}" class="fw-bold">{{ $found->title }}</a>
                                <span class="score-pill">{{ $match->score }} <small>{{ $match->source_label }}</small></span>
                            </div>
                            <div class="score-bar {{ $match->score < 70 ? 'mid' : '' }}"><span style="width: {{ $match->score }}%"></span></div>
                            <div class="small-caption">{{ $found->event_at->diffForHumans() }} · {{ $found->place_name }} · {{ $match->distance_label }} dari lokasi hilang</div>
                            @if ($match->ai_reason)
                                <div class="match-reason mt-1">{{ $match->ai_reason }}</div>
                            @endif
                            <div class="d-flex gap-2 mt-2">
                                <a href="{{ route('claims.create', ['report' => $found, 'lost' => $lost->id]) }}" class="btn btn-brand btn-sm">Ajukan klaim</a>
                                <a href="{{ route('reports.show', $found) }}" class="btn btn-outline-ink btn-sm">Lihat</a>
                                <form method="POST" action="{{ route('matches.dismiss', $match) }}">
                                    @csrf
                                    <button class="btn btn-link btn-sm text-muted">Bukan barang saya</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        <div class="section-card p-0 overflow-hidden">
            @forelse ($alerts as $alert)
                <a href="{{ route('alerts.open', $alert) }}" class="d-flex gap-3 px-3 py-3 border-bottom text-body {{ $alert->read_at ? '' : 'bg-warning-subtle' }}" style="{{ $alert->read_at ? '' : 'background:#FFFBF5 !important' }}">
                    <i class="bi bi-{{ $alert->icon }} fs-5 text-secondary"></i>
                    <div class="flex-grow-1">
                        <div class="{{ $alert->read_at ? '' : 'fw-bold' }}">{{ $alert->title }}</div>
                        @if ($alert->body)<div class="small text-muted">{{ $alert->body }}</div>@endif
                        <div class="small-caption">{{ $alert->created_at->diffForHumans() }}</div>
                    </div>
                    @unless ($alert->read_at)<span class="bell-count position-static" style="min-width:10px;height:10px;padding:0" aria-label="Belum dibaca"></span>@endunless
                </a>
            @empty
                <div class="empty-state"><i class="bi bi-bell-slash"></i>Belum ada notifikasi.</div>
            @endforelse
        </div>
        <div class="mt-3 d-flex justify-content-center">{{ $alerts->links() }}</div>
    </div>
</div>
@endsection
