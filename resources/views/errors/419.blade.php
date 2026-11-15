@extends('layouts.app')

@section('title', 'Sesi berakhir')

@section('content')
<div class="section-card empty-state my-5 mx-auto" style="max-width: 520px">
    <i class="bi bi-hourglass-bottom"></i>
    <h1 class="h4 text-body">Sesi sudah berakhir</h1>
    <p>Halaman terlalu lama dibuka. Muat ulang lalu coba lagi.</p>
    <a href="{{ url()->previous() }}" class="btn btn-ink btn-sm">Muat ulang</a>
</div>
@endsection
