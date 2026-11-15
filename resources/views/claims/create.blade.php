@extends('layouts.app')

@section('title', 'Ajukan Klaim')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="page-title">Ajukan klaim</h1>
        <p class="page-sub">Admin akan mencocokkan jawabanmu dengan ciri rahasia yang dicatat penemu.</p>

        <div class="section-card d-flex gap-3 align-items-center">
            @if ($report->thumb_url)
                <img src="{{ $report->thumb_url }}" class="thumb thumb-lg" alt="">
            @else
                <div class="thumb thumb-lg"><div class="photo-empty found"><i class="bi bi-{{ $report->category->icon }}"></i></div></div>
            @endif
            <div class="min-w-0">
                @include('partials.type-label', ['type' => 'found'])
                <div class="fw-bold mt-1">{{ $report->title }}</div>
                <div class="small-caption">{{ $report->event_at->diffForHumans() }} · {{ $report->place_name }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('claims.store', $report) }}" class="section-card" novalidate>
            @csrf
            <div class="mb-3">
                <label for="secret_answer" class="form-label required">Sebutkan ciri khusus barang yang tidak terlihat di foto</label>
                <textarea name="secret_answer" id="secret_answer" rows="3" class="form-control @error('secret_answer') is-invalid @enderror"
                          placeholder="Contoh: isi dompet, stiker di bagian bawah, goresan, nama yang tertulis…">{{ old('secret_answer') }}</textarea>
                @error('secret_answer')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="proof" class="form-label required">Bukti kepemilikan</label>
                <textarea name="proof" id="proof" rows="3" class="form-control @error('proof') is-invalid @enderror"
                          placeholder="Ceritakan kapan dan di mana barang hilang, dan bukti yang bisa kamu tunjukkan saat serah terima.">{{ old('proof') }}</textarea>
                @error('proof')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-4">
                <label for="lost_report_id" class="form-label">Tautkan laporan hilang saya (opsional)</label>
                <select name="lost_report_id" id="lost_report_id" class="form-select @error('lost_report_id') is-invalid @enderror">
                    <option value="">Tidak ada</option>
                    @foreach ($myLostReports as $lost)
                        <option value="{{ $lost->id }}" @selected(old('lost_report_id', $claim->lost_report_id) == $lost->id)>{{ $lost->title }} ({{ $lost->event_at->translatedFormat('d M') }})</option>
                    @endforeach
                </select>
                @error('lost_report_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Laporan hilangmu ikut ditandai Selesai setelah serah terima.</div>
            </div>
            <div class="secret-note mb-4"><i class="bi bi-info-circle"></i><span>Klaim palsu akan ditolak, dan akun bisa dinonaktifkan. Chat dengan penemu terbuka setelah admin menyetujui klaim.</span></div>
            <div class="form-actions">
                <a href="{{ route('reports.show', $report) }}" class="btn btn-outline-ink">Batal</a>
                <button class="btn btn-brand px-4">Kirim Klaim</button>
            </div>
        </form>
    </div>
</div>
@endsection
