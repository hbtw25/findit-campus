@extends('layouts.app')

@section('title', 'Klaim #' . $claim->id)

@section('content')
@php
    $steps = [
        ['label' => 'Diajukan', 'state' => 'done'],
        ['label' => 'Diverifikasi', 'state' => match ($claim->status) {
            'pending' => 'current', 'rejected', 'cancelled' => 'fail', default => 'done',
        }],
        ['label' => 'Diserahkan', 'state' => match ($claim->status) {
            'returned' => 'done', 'approved' => 'current', default => '',
        }],
    ];
@endphp

<div class="row justify-content-center">
    <div class="col-lg-9">
        <nav class="mb-3 small"><a href="{{ route('claims.index') }}" class="text-muted"><i class="bi bi-arrow-left me-1"></i>Semua klaim</a></nav>

        <div class="section-card">
            <div class="d-flex gap-3 align-items-center mb-3">
                @if ($claim->report->thumb_url)
                    <img src="{{ $claim->report->thumb_url }}" class="thumb thumb-lg" alt="">
                @endif
                <div class="flex-grow-1 min-w-0">
                    <span class="status-badge s-{{ $claim->status }}">{{ $claim->status_label }}</span>
                    <h1 class="h4 mt-1 mb-0"><a href="{{ route('reports.show', $claim->report) }}">{{ $claim->report->title }}</a></h1>
                    <div class="small-caption">Ditemukan oleh {{ $claim->report->user->short_name }} · {{ $claim->report->place_name }}</div>
                </div>
            </div>

            <div class="timeline" aria-label="Status klaim">
                @foreach ($steps as $step)
                    <div class="step {{ $step['state'] }}">
                        <span class="node">
                            @if ($step['state'] === 'done')<i class="bi bi-check-lg"></i>
                            @elseif ($step['state'] === 'fail')<i class="bi bi-x-lg"></i>
                            @else {{ $loop->iteration }} @endif
                        </span>
                        {{ $step['label'] }}
                    </div>
                @endforeach
            </div>

            @if ($claim->status === 'pending')
                <div class="secret-note"><i class="bi bi-hourglass-split"></i><span>Menunggu verifikasi admin. Kamu akan mendapat notifikasi saat klaim diputuskan.</span></div>
            @elseif ($claim->status === 'rejected')
                <div class="flash flash-error"><i class="bi bi-x-circle-fill"></i><div><strong>Klaim ditolak.</strong> {{ $claim->admin_note }}</div></div>
            @elseif ($claim->status === 'approved')
                <div class="flash flash-success"><i class="bi bi-check-circle-fill"></i><div><strong>Klaim disetujui admin.</strong> Atur waktu dan tempat serah terima lewat chat di bawah.@if ($claim->admin_note) Catatan admin: {{ $claim->admin_note }}@endif</div></div>
            @elseif ($claim->status === 'returned')
                <div class="flash flash-success"><i class="bi bi-bag-check-fill"></i><div><strong>Barang sudah diserahkan</strong> {{ $claim->returned_at?->diffForHumans() }}. Terima kasih sudah memakai FindIt Campus.</div></div>
            @elseif ($claim->status === 'cancelled')
                <div class="flash flash-error"><i class="bi bi-slash-circle"></i><div>Klaim dibatalkan.@if ($claim->admin_note) {{ $claim->admin_note }}@endif</div></div>
            @endif

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="small-caption fw-bold">Jawaban ciri khusus</div>
                    <p class="mb-0">{{ $claim->secret_answer }}</p>
                </div>
                <div class="col-md-6">
                    <div class="small-caption fw-bold">Bukti kepemilikan</div>
                    <p class="mb-0">{{ $claim->proof }}</p>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                @if ($isClaimant && $claim->status === 'pending')
                    <form method="POST" action="{{ route('claims.cancel', $claim) }}" data-confirm="Batalkan klaim ini?">
                        @csrf
                        <button class="btn btn-outline-danger btn-sm">Batalkan klaim</button>
                    </form>
                @endif
                @if ($claim->status === 'approved' && ($isFinder || Auth::user()->isAdmin()))
                    <form method="POST" action="{{ route('claims.returned', $claim) }}" data-confirm="Konfirmasi barang sudah diserahkan ke pemiliknya?">
                        @csrf
                        <button class="btn btn-found"><i class="bi bi-bag-check me-1"></i>Konfirmasi Diserahkan</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Chat serah terima (AJAX) --}}
        @if ($claim->chatOpen())
            <div class="section-card">
                <div class="section-title"><i class="bi bi-chat-dots"></i>Chat serah terima</div>
                <div class="chat-box" id="chat-box" aria-live="polite">
                    <div class="text-center small-caption" id="chat-empty">Belum ada pesan. Sapa dulu dan sepakati tempat bertemu.</div>
                </div>
                @if ($claim->status === 'approved')
                    <form class="chat-form" id="chat-form" autocomplete="off">
                        <input type="text" class="form-control" id="chat-input" maxlength="1000" placeholder="Tulis pesan…" aria-label="Tulis pesan">
                        <button class="btn btn-ink" aria-label="Kirim"><i class="bi bi-send"></i></button>
                    </form>
                @else
                    <div class="chat-form justify-content-center small-caption">Chat ditutup karena serah terima sudah selesai.</div>
                @endif
                <div class="small-caption mt-2"><i class="bi bi-shield-check me-1"></i>Bertemulah di tempat ramai di dalam kampus, misalnya lobby gedung atau pos satpam.</div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
    @if ($claim->chatOpen())
        <script>
            window.FINDIT_CHAT = { url: "{{ route('ajax.messages.index', $claim) }}", open: {{ $claim->status === 'approved' ? 'true' : 'false' }} };
        </script>
        <script src="{{ asset('js/chat.js') }}"></script>
    @endif
@endsection
