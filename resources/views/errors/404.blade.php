@extends('layouts.app')

@section('title', 'Tidak ditemukan')

@section('content')
<div class="section-card empty-state my-5 mx-auto" style="max-width: 520px">
    <i class="bi bi-signpost-split"></i>
    <h1 class="h4 text-body">Halaman tidak ditemukan</h1>
    <p>Laporan mungkin sudah dihapus atau tautannya salah.</p>
    <a href="{{ route('map') }}" class="btn btn-ink btn-sm">Kembali ke peta</a>
</div>
@endsection
