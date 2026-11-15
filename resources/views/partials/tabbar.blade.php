{{-- Navigasi bawah khusus layar HP --}}
<nav class="mobile-tabbar d-lg-none" aria-label="Navigasi utama">
    <a href="{{ route('map') }}" class="{{ request()->routeIs('map') ? 'active' : '' }}"><i class="bi bi-map"></i>Peta</a>
    <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.index') ? 'active' : '' }}"><i class="bi bi-grid"></i>Jelajah</a>
    <a href="{{ route('reports.create') }}" class="tab-report"><i class="bi bi-plus-lg"></i>Lapor</a>
    <a href="{{ route('claims.index') }}" class="{{ request()->routeIs('claims.*') ? 'active' : '' }}"><i class="bi bi-patch-check"></i>Klaim</a>
    <a href="{{ Auth::check() ? route('reports.mine') : route('login') }}" class="{{ request()->routeIs('reports.mine') ? 'active' : '' }}"><i class="bi bi-person"></i>Saya</a>
</nav>
