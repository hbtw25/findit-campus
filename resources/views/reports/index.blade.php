@extends('layouts.app')

@section('title', 'Jelajah Laporan')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
    <div>
        <h1 class="page-title">Jelajah laporan</h1>
        <p class="page-sub mb-0">{{ $reports->total() }} laporan aktif di kampus.</p>
    </div>
    <a href="{{ route('map') }}" class="btn btn-outline-ink"><i class="bi bi-map me-1"></i>Lihat di peta</a>
</div>

<form method="GET" action="{{ route('reports.index') }}" class="section-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-lg-4">
            <label for="q" class="form-label">Kata kunci</label>
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="search" name="q" id="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Contoh: dompet coklat">
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <label for="type" class="form-label">Jenis</label>
            <select name="type" id="type" class="form-select">
                <option value="">Semua</option>
                <option value="lost" @selected(($filters['type'] ?? '') === 'lost')>Hilang</option>
                <option value="found" @selected(($filters['type'] ?? '') === 'found')>Ditemukan</option>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label for="category" class="form-label">Kategori</label>
            <select name="category" id="category" class="form-select">
                <option value="">Semua</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category'] ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label for="location" class="form-label">Gedung</label>
            <select name="location" id="location" class="form-select">
                <option value="">Semua</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}" @selected(($filters['location'] ?? '') == $location->id)>{{ $location->short_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-1">
            <label for="days" class="form-label">Periode</label>
            <select name="days" id="days" class="form-select">
                <option value="">Semua</option>
                @foreach ([7, 30, 90] as $d)
                    <option value="{{ $d }}" @selected(($filters['days'] ?? '') == $d)>{{ $d }} hari</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-lg-1 d-grid">
            <button class="btn btn-ink">Cari</button>
        </div>
    </div>
</form>

@if ($reports->isEmpty())
    <div class="section-card empty-state">
        <i class="bi bi-binoculars"></i>
        Tidak ada laporan yang cocok dengan filter ini.
        <div class="mt-3"><a href="{{ route('reports.create') }}" class="btn btn-brand btn-sm">Buat laporan</a></div>
    </div>
@else
    <div class="row g-3">
        @foreach ($reports as $report)
            <div class="col-6 col-md-4 col-xl-3">
                @include('partials.report-card', ['report' => $report])
            </div>
        @endforeach
    </div>
    <div class="mt-4 d-flex justify-content-center">{{ $reports->links() }}</div>
@endif
@endsection
