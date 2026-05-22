<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Dharma Tech OS</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌿</text></svg>">
    @stack('head')
</head>
<body>

<!-- ─── SIDEBAR OVERLAY (mobile) ─────────────────────────────── -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ─── SIDEBAR ─────────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🌿</div>
        <h1>Dharma Tech OS</h1>
        <span>ERP Ecosystem Edition</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu Utama</div>

        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="nav-icon">📊</span> Ringkasan Eksekutif
        </a>
        <a href="{{ route('inventaris') }}" class="nav-item {{ request()->routeIs('inventaris') ? 'active' : '' }}">
            <span class="nav-icon">🗄️</span> Master Inventaris
        </a>
        <a href="{{ route('grn') }}" class="nav-item {{ request()->routeIs('grn') ? 'active' : '' }}">
            <span class="nav-icon">⚖️</span> Operasional (GRN)
        </a>
        <a href="{{ route('pabrik') }}" class="nav-item {{ request()->routeIs('pabrik') ? 'active' : '' }}">
            <span class="nav-icon">🏭</span> Pabrikasi
        </a>
        <a href="{{ route('finance') }}" class="nav-item {{ request()->routeIs('finance') ? 'active' : '' }}">
            <span class="nav-icon">💰</span> Finance & Laporan
        </a>
        <a href="{{ route('sales') }}" class="nav-item {{ request()->routeIs('sales') ? 'active' : '' }}">
            <span class="nav-icon">🚚</span> Sales & Distribusi
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                <span>🚪</span> Keluar
            </button>
        </form>
    </div>
</aside>

<!-- ─── MAIN CONTENT ─────────────────────────────────────────── -->
<div class="main-content">

    <!-- TOP BAR -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" onclick="toggleSidebar()" id="hamburgerBtn" aria-label="Toggle Menu">
                ☰
            </button>
            <div>
                <div class="page-title">@yield('page-title', 'Dashboard')</div>
                <div class="page-subtitle">@yield('page-subtitle', 'Dharma Tech OS ERP')</div>
            </div>
        </div>
        <div class="topbar-right">
            <span class="topbar-time" id="liveTime"></span>
        </div>
    </header>

    <!-- PAGE BODY -->
    <main class="page-content">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success">
                <span>✅</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning">
                <span>⚠️</span>
                <div>{{ session('warning') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                <span>❌</span>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if($errors->any() && !$errors->has('username') && !$errors->has('password'))
            <div class="alert alert-danger">
                <span>❌</span>
                <ul style="margin:0;padding-left:16px;">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<!-- ─── BOTTOM NAV (mobile only) ────────────────────────────── -->
<nav class="bottom-nav">
    <a href="{{ route('dashboard') }}" class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <span class="bn-icon">📊</span>Dashboard
    </a>
    <a href="{{ route('inventaris') }}" class="bottom-nav-item {{ request()->routeIs('inventaris') ? 'active' : '' }}">
        <span class="bn-icon">🗄️</span>Stok
    </a>
    <a href="{{ route('grn') }}" class="bottom-nav-item {{ request()->routeIs('grn') ? 'active' : '' }}">
        <span class="bn-icon">⚖️</span>GRN
    </a>
    <a href="{{ route('pabrik') }}" class="bottom-nav-item {{ request()->routeIs('pabrik') ? 'active' : '' }}">
        <span class="bn-icon">🏭</span>Pabrik
    </a>
    <a href="{{ route('finance') }}" class="bottom-nav-item {{ request()->routeIs('finance') ? 'active' : '' }}">
        <span class="bn-icon">💰</span>Finance
    </a>
    <a href="{{ route('sales') }}" class="bottom-nav-item {{ request()->routeIs('sales') ? 'active' : '' }}">
        <span class="bn-icon">🚚</span>Sales
    </a>
</nav>

<script>
// ── Live Clock ──────────────────────────────────────────
function updateClock() {
    const el = document.getElementById('liveTime');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleString('id-ID', { hour:'2-digit', minute:'2-digit', weekday:'short', day:'numeric', month:'short' });
    }
}
updateClock();
setInterval(updateClock, 30000);

// ── Sidebar Toggle (mobile) ─────────────────────────────
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isOpen  = sidebar.classList.toggle('open');
    overlay.classList.toggle('active', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Auto-close sidebar on resize to desktop
window.addEventListener('resize', () => {
    if (window.innerWidth > 900) closeSidebar();
});

// ── Auto-dismiss alerts ─────────────────────────────────
document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => {
        a.style.transition = 'opacity .5s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 500);
    }, 5000);
});
</script>

@stack('scripts')
</body>
</html>
