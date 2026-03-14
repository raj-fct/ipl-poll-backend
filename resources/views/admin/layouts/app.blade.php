<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - IPL Poll</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary: #1a1a2e;
            --accent: #e94560;
        }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--primary);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: transform 0.3s;
        }
        .sidebar .brand {
            padding: 1.25rem;
            color: #fff;
            font-size: 1.3rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .brand span { color: var(--accent); }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.7rem 1.25rem;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.05);
            border-left-color: var(--accent);
        }
        .sidebar .nav-link i { width: 24px; margin-right: 8px; }
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        .top-bar {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 0.75rem 1.5rem;
        }
        .content-area { padding: 1.5rem; }
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 1.25rem;
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
        }
        .stat-card .stat-value { font-size: 1.5rem; font-weight: 700; }
        .stat-card .stat-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-radius: 10px; }
        .card-header { background: #fff; border-bottom: 1px solid #f0f0f0; font-weight: 600; }
        .table th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; border-top: none; }
        .badge-upcoming { background: #ffc107; color: #000; }
        .badge-live { background: #dc3545; color: #fff; }
        .badge-completed { background: #198754; color: #fff; }
        .badge-cancelled { background: #6c757d; color: #fff; }
        .badge-pending { background: #0dcaf0; color: #000; }
        .badge-won { background: #198754; color: #fff; }
        .badge-lost { background: #dc3545; color: #fff; }
        .badge-refunded { background: #6c757d; color: #fff; }
        .pagination { margin-bottom: 0; font-size: 0.875rem; }
        .pagination .page-link { padding: 0.35rem 0.65rem; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
    {{-- Sidebar --}}
    <nav class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-trophy-fill"></i> IPL <span>Poll</span>
        </div>
        <ul class="nav flex-column mt-2">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.teams.*') ? 'active' : '' }}" href="{{ route('admin.teams.index') }}">
                    <i class="bi bi-shield"></i> Teams
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.matches.*') ? 'active' : '' }}" href="{{ route('admin.matches.index') }}">
                    <i class="bi bi-calendar-event"></i> Matches
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.polls.*') ? 'active' : '' }}" href="{{ route('admin.polls.index') }}">
                    <i class="bi bi-bar-chart-line"></i> Polls
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}" href="{{ route('admin.transactions.index') }}">
                    <i class="bi bi-coin"></i> Transactions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
        </ul>
    </nav>

    {{-- Main Content --}}
    <div class="main-content">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-sm btn-outline-secondary d-md-none me-2" onclick="document.getElementById('sidebar').classList.toggle('show')">
                    <i class="bi bi-list"></i>
                </button>
                <span class="fw-semibold">@yield('title', 'Dashboard')</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">{{ auth()->user()->name }}</span>
                <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        <div class="content-area">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
