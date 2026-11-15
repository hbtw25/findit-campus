@extends('layouts.app')

@section('title', 'Daftar')
@section('main_class', '')
@section('hide_tabbar', true)

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div class="brand mb-4"><span class="brand-mark"><i class="bi bi-geo-alt-fill"></i></span> FindIt <small>Campus</small></div>
        <h1 class="h3 mb-1">Buat akun</h1>
        <p class="text-muted mb-4">Khusus civitas Telkom University.</p>

        <form method="POST" action="{{ route('register.store') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label required">Nama lengkap</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" autocomplete="name" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="nim" class="form-label required">NIM / NIP</label>
                <input type="text" name="nim" id="nim" inputmode="numeric" class="form-control @error('nim') is-invalid @enderror" value="{{ old('nim') }}" required>
                @error('nim')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="email" class="form-label required">Email kampus</label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                       placeholder="nama@{{ $domains[0] ?? 'student.telkomuniversity.ac.id' }}" autocomplete="email" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label required">Kata sandi</label>
                <div class="position-relative">
                    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required>
                    <button type="button" class="pw-toggle" data-toggle-password="#password" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button>
                </div>
                <div class="form-text">Minimal 8 karakter.</div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-4">
                <label for="password_confirmation" class="form-label required">Ulangi kata sandi</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-brand w-100 py-2">Daftar</button>
        </form>

        <p class="text-center mt-4 mb-0">Sudah punya akun? <a href="{{ route('login') }}" class="fw-bold link-underline">Masuk</a></p>
    </div>
</div>
@endsection
