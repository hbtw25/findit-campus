@extends('layouts.admin')

@section('title', 'Kelola Pengguna')

@section('admin_content')
<h1 class="page-title">Pengguna</h1>
<p class="page-sub">Nonaktifkan akun yang menyalahgunakan aplikasi. Akun nonaktif tidak bisa masuk.</p>

<div class="section-card">
    <table id="users-table" class="table table-hover w-100" data-url="{{ route('admin.users.data') }}">
        <thead>
            <tr>
                <th>Nama</th>
                <th>NIM/NIP</th>
                <th>Email</th>
                <th>Peran</th>
                <th>Laporan</th>
                <th>Klaim</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
    </table>
</div>
@endsection
