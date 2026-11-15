<nav class="app-nav">
    <div class="container-fluid h-100 px-3 px-lg-4 d-flex align-items-center gap-3">
        <a href="{{ route('map') }}" class="brand me-lg-3">
            <span class="brand-mark"><i class="bi bi-geo-alt-fill"></i></span>
            FindIt <small>Campus</small>
        </a>

        <ul class="nav d-none d-lg-flex gap-1">
            <li><a class="nav-link {{ request()->routeIs('map') ? 'active' : '' }}" href="{{ route('map') }}">Peta</a></li>
            <li><a class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}" href="{{ route('reports.index') }}">Jelajah</a></li>
            @auth
                <li><a class="nav-link {{ request()->routeIs('reports.mine') ? 'active' : '' }}" href="{{ route('reports.mine') }}">Laporan Saya</a></li>
                <li><a class="nav-link {{ request()->routeIs('claims.*') ? 'active' : '' }}" href="{{ route('claims.index') }}">Klaim</a></li>
                @if (Auth::user()->isAdmin())
                    <li><a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Admin</a></li>
                @endif
            @endauth
        </ul>

        <div class="ms-auto d-flex align-items-center gap-2">
            @auth
                <div class="dropdown">
                    <button class="icon-btn" id="bell-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                        <i class="bi bi-bell"></i>
                        <span class="bell-count" id="bell-count" @if (! $unreadAlerts) style="display:none" @endif>{{ $unreadAlerts }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end alert-menu shadow-sm">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong>Notifikasi</strong>
                            <a href="{{ route('alerts.index') }}" class="small-caption">Lihat semua</a>
                        </div>
                        <div id="bell-list">
                            <div class="empty-state py-4"><span class="spinner-border spinner-border-sm"></span></div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('reports.create') }}" class="btn btn-brand d-none d-lg-inline-flex align-items-center gap-1">
                    <i class="bi bi-plus-lg"></i> Laporkan
                </a>

                <div class="dropdown">
                    <button class="btn p-0 border-0" data-bs-toggle="dropdown" aria-label="Menu akun">
                        <span class="avatar">{{ Auth::user()->initials }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="px-3 py-2">
                            <div class="fw-bold">{{ Auth::user()->name }}</div>
                            <div class="small-caption">{{ Auth::user()->email }}</div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('reports.mine') }}"><i class="bi bi-card-list me-2"></i>Laporan Saya</a></li>
                        <li><a class="dropdown-item" href="{{ route('claims.index') }}"><i class="bi bi-patch-check me-2"></i>Klaim</a></li>
                        @if (Auth::user()->isAdmin())
                            <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Panel Admin</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-ink btn-sm">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-brand btn-sm">Daftar</a>
            @endauth
        </div>
    </div>
</nav>
