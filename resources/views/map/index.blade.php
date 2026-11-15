@extends('layouts.app')

@section('title', 'Peta Global')
@section('main_class', '')
@section('no_flash', true)

@section('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/markercluster/MarkerCluster.css') }}">
@endsection

@section('content')
<div class="map-layout">
    {{-- Panel kiri (desktop): filter + daftar laporan di sekitar --}}
    <aside class="map-panel">
        <div class="map-panel-head">
            <form id="map-filter" autocomplete="off">
                <div class="search-box mb-2">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" class="form-control" placeholder="Cari barang, warna, merek…" aria-label="Cari barang">
                </div>
                <div class="chips mb-2" role="radiogroup" aria-label="Jenis laporan">
                    <input type="radio" class="chip-input" name="type" id="t-all" value="" checked>
                    <label class="chip" for="t-all">Semua</label>
                    <input type="radio" class="chip-input" name="type" id="t-lost" value="lost">
                    <label class="chip" for="t-lost"><span class="dot lost"></span>Hilang</label>
                    <input type="radio" class="chip-input" name="type" id="t-found" value="found">
                    <label class="chip" for="t-found"><span class="dot found"></span>Ditemukan</label>
                </div>
                <div class="row g-2">
                    <div class="col-7">
                        <select name="category" class="form-select form-select-sm" aria-label="Kategori">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-5">
                        <select name="days" class="form-select form-select-sm" aria-label="Periode">
                            <option value="7">7 hari</option>
                            <option value="30" selected>30 hari</option>
                            <option value="90">90 hari</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="px-3 pt-3 pb-1 d-flex justify-content-between align-items-center">
            <strong class="small">Di sekitar sini</strong>
            <span class="small-caption" id="list-count">Memuat…</span>
        </div>
        <div class="map-panel-list" id="report-list"></div>
        <div class="map-panel-foot">
            <span><span class="type-label lost me-1"><i class="bi bi-search"></i> {{ $totals['lost'] }}</span>
                <span class="type-label found"><i class="bi bi-check-lg"></i> {{ $totals['found'] }}</span> aktif</span>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="toggle-buildings" checked>
                <label class="form-check-label" for="toggle-buildings">Gedung</label>
            </div>
        </div>
    </aside>

    {{-- Peta --}}
    <section class="map-stage">
        <div id="global-map" aria-label="Peta barang hilang dan ditemukan di kampus"></div>

        {{-- Pesan flash tampil melayang di atas peta lalu hilang sendiri --}}
        @if (session('success') || session('error'))
            <div class="map-toast" id="map-toast">@include('partials.flash')</div>
        @endif

        {{-- Filter melayang khusus HP --}}
        <div class="map-overlay-top">
            <div class="chips" id="mobile-chips">
                <input type="radio" class="chip-input" name="m-type" id="m-all" value="" checked>
                <label class="chip" for="m-all">Semua</label>
                <input type="radio" class="chip-input" name="m-type" id="m-lost" value="lost">
                <label class="chip" for="m-lost"><span class="dot lost"></span>Hilang</label>
                <input type="radio" class="chip-input" name="m-type" id="m-found" value="found">
                <label class="chip" for="m-found"><span class="dot found"></span>Ditemukan</label>
                <a href="{{ route('reports.index') }}" class="chip"><i class="bi bi-sliders"></i>Filter</a>
            </div>
        </div>

        <button type="button" class="map-ctrl" id="btn-locate" style="bottom: 96px" aria-label="Lokasi saya"><i class="bi bi-crosshair"></i></button>
        <button type="button" class="map-ctrl d-lg-none is-on" id="btn-buildings" style="bottom: 148px" aria-label="Tampilkan gedung"><i class="bi bi-buildings"></i></button>
        <span class="map-count" id="map-count">Memuat laporan…</span>
        <div class="map-loading" id="map-loading"><span class="spinner-border spinner-border-sm me-2"></span>Memuat</div>

        <a href="{{ route('reports.create') }}" class="btn btn-brand map-fab"><i class="bi bi-plus-lg me-1"></i>Laporkan</a>

        {{-- Kartu pratinjau untuk HP --}}
        <div class="map-sheet" id="map-sheet" role="dialog" aria-label="Pratinjau laporan">
            <button type="button" class="sheet-close" aria-label="Tutup">&times;</button>
            <div class="sheet-inner" id="sheet-content"></div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('vendor/markercluster/leaflet.markercluster.js') }}"></script>
    <script>
        window.FINDIT_MAP = {
            center: [{{ $campus['lat'] }}, {{ $campus['lng'] }}],
            zoom: {{ $campus['zoom'] }},
            reportsUrl: "{{ route('ajax.map.reports') }}",
            locationsUrl: "{{ route('ajax.locations') }}",
        };
    </script>
    <script src="{{ asset('js/map.js') }}"></script>
@endsection
