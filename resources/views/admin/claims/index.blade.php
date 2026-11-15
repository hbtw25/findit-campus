@extends('layouts.admin')

@section('title', 'Verifikasi Klaim')

@section('admin_content')
<h1 class="page-title">Verifikasi klaim</h1>
<p class="page-sub">Bandingkan jawaban pengklaim dengan ciri rahasia dari penemu sebelum menyetujui.</p>

<div class="section-card">
    <div class="d-flex gap-2 mb-3">
        <select class="form-select form-select-sm w-auto" id="filter-claim-status" aria-label="Filter status">
            <option value="Menunggu" selected>Menunggu</option>
            <option value="">Semua status</option>
            <option>Disetujui</option>
            <option>Ditolak</option>
            <option>Diserahkan</option>
            <option>Dibatalkan</option>
        </select>
    </div>
    <table id="claims-table" class="table table-hover w-100" data-url="{{ route('admin.claims.data') }}">
        <thead>
            <tr>
                <th>Barang</th>
                <th>Pengklaim</th>
                <th>NIM</th>
                <th>Status</th>
                <th>Diajukan</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
    </table>
</div>
@endsection
