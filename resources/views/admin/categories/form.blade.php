@extends('layouts.admin')

@section('title', $title . ' Kategori')

@section('admin_content')
<div class="row">
    <div class="col-lg-6">
        <h1 class="page-title mb-3">Form {{ $title }} Kategori</h1>
        <form class="section-card" method="POST" action="{{ $route }}">
            @csrf
            @if ($method === 'PUT')
                @method('PUT')
            @endif
            <div class="mb-3">
                <label for="name" class="form-label required">Nama</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-4">
                <label for="icon" class="form-label required">Ikon (nama Bootstrap Icons)</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-{{ old('icon', $category->icon) }}" id="icon-preview"></i></span>
                    <input type="text" name="icon" id="icon" class="form-control @error('icon') is-invalid @enderror" value="{{ old('icon', $category->icon) }}" placeholder="wallet2">
                    @error('icon')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-text">Lihat daftar ikon di icons.getbootstrap.com, contoh: wallet2, key, phone, backpack2.</div>
            </div>
            <div class="form-actions">
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-ink">Batal</a>
                <button type="submit" class="btn btn-brand">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('admin_scripts')
<script>
    $('#icon').on('input', function () {
        $('#icon-preview').attr('class', 'bi bi-' + $(this).val());
    });
</script>
@endsection
