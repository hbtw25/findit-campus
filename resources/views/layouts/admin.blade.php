@extends('layouts.app')

@section('main_class', '')
@section('hide_tabbar', true)
@section('no_flash', true)

@section('styles')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}">
    @yield('admin_styles')
@endsection

@section('content')
<div class="admin-shell">
    <aside class="admin-side">
        <div class="side-label">Panel Admin</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i>Dashboard</a>
        <a href="{{ route('admin.claims.index') }}" class="nav-link {{ request()->routeIs('admin.claims.*') ? 'active' : '' }}"><i class="bi bi-patch-check"></i>Verifikasi Klaim</a>
        <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"><i class="bi bi-card-list"></i>Laporan</a>
        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-people"></i>Pengguna</a>
        <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><i class="bi bi-tags"></i>Kategori</a>
        <div class="side-label">Aplikasi</div>
        <a href="{{ route('map') }}" class="nav-link"><i class="bi bi-map"></i>Peta Global</a>
    </aside>
    <div class="admin-main">
        @include('partials.flash')
        @yield('admin_content')
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/admin-tables.js') }}"></script>
    @yield('admin_scripts')
@endsection
