@extends('layouts.admin')

@section('title', 'Kelola Laporan')

@section('admin_content')
<h1 class="page-title">Laporan</h1>
<p class="page-sub">Data dimuat dari JSON lewat jQuery DataTables. Sembunyikan laporan yang melanggar aturan.</p>

<div class="section-card">
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <select class="form-select form-select-sm w-auto" id="filter-type" aria-label="Filter jenis">
            <option value="">Semua jenis</option>
            <option value="Hilang">Hilang</option>
            <option value="Ditemukan">Ditemukan</option>
        </select>
        <select class="form-select form-select-sm w-auto" id="filter-status" aria-label="Filter status">
            <option value="">Semua status</option>
            <option>Aktif</option>
            <option>Proses Klaim</option>
            <option>Selesai</option>
            <option>Ditutup</option>
        </select>
    </div>
    <table id="reports-table" class="table table-hover w-100" data-url="{{ route('admin.reports.data') }}">
        <thead>
            <tr>
                <th>Barang</th>
                <th>Jenis</th>
                <th>Kategori</th>
                <th>Gedung</th>
                <th>Pelapor</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
    </table>
</div>
@endsection
