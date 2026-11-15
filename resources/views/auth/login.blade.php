@extends('layouts.app')

@section('title', 'Masuk')
@section('main_class', '')
@section('hide_tabbar', true)
@section('no_flash', true)

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div class="brand mb-4"><span class="brand-mark"><i class="bi bi-geo-alt-fill"></i></span> FindIt <small>Campus</small></div>
        <h1 class="h3 mb-1">Masuk ke FindIt</h1>
        <p class="text-muted mb-4">Laporkan dan temukan barang hilang di kampus.</p>

        @include('partials.flash')

        <form method="POST" action="{{ route('login.attempt') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" placeholder="nama@student.telkomuniversity.ac.id" autocomplete="username" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Kata sandi</label>
                <div class="position-relative">
                    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
                           autocomplete="current-password" required>
                    <button type="button" class="pw-toggle" data-toggle-password="#password" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button>
                </div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Ingat saya</label>
            </div>
            <button type="submit" class="btn btn-brand w-100 py-2">Masuk</button>
        </form>

        <p class="text-center mt-4 mb-0">Belum punya akun? <a href="{{ route('register') }}" class="fw-bold link-underline">Daftar</a></p>
        <p class="small-caption text-center mt-3 mb-0">Gunakan email kampus @student.telkomuniversity.ac.id</p>
    </div>
</div>
@endsection
