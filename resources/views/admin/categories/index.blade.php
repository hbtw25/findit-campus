@extends('layouts.admin')

@section('title', 'Kategori')

@section('admin_content')
<div class="d-flex justify-content-between align-items-end mb-3">
    <div>
        <h1 class="page-title">Kategori</h1>
        <p class="page-sub mb-0">Kategori dipakai untuk filter dan syarat auto-match.</p>
    </div>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-brand">Tambah</a>
</div>

<div class="section-card">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Ikon</th>
                <th>Nama</th>
                <th>Slug</th>
                <th>Jumlah laporan</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td><i class="bi bi-{{ $category->icon }} fs-5"></i></td>
                    <td class="fw-semibold">{{ $category->name }}</td>
                    <td class="text-muted">{{ $category->slug }}</td>
                    <td>{{ $category->reports_count }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-sm btn-outline-ink">Edit</a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}" style="display:inline" onsubmit="return confirm('Yakin hapus?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
