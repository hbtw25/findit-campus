@extends('layouts.app')

@section('title', 'Akses ditolak')

@section('content')
<div class="section-card empty-state my-5 mx-auto" style="max-width: 520px">
    <i class="bi bi-shield-lock"></i>
    <h1 class="h4 text-body">Akses ditolak</h1>
    <p>{{ $exception->getMessage() ?: 'Kamu tidak punya izin untuk membuka halaman ini.' }}</p>
    <a href="{{ route('map') }}" class="btn btn-ink btn-sm">Kembali ke peta</a>
</div>
@endsection
