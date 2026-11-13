<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title','Weaving ERP') · BroshTech</title>
  <link rel="stylesheet" href="{{ asset('css/weaving.css') }}">
</head>
<body>
<div class="app-shell">

  <aside class="sidebar">

    {{-- Brand --}}
    <div class="brand">
      <div class="logo">W</div>
      <div>
        <strong>Weaving ERP</strong>
        <small>BroshTech</small>
      </div>
    </div>

    {{-- Main nav --}}
    <div class="side-caption">WORKSPACE</div>
    <nav>

      <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
          </svg>
        </span>
        <span>Dashboard</span>
      </a>

      <a href="{{ route('contracts.index') }}" class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/>
          </svg>
        </span>
        <span>Contracts</span>
      </a>

      <a href="{{ route('yarn.index') }}" class="{{ request()->routeIs('yarn.*') ? 'active' : '' }}">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            <line x1="2" y1="12" x2="22" y2="12"/>
          </svg>
        </span>
        <span>Yarn</span>
      </a>

      <a href="{{ route('fabric.index') }}" class="{{ request()->routeIs('fabric.*') ? 'active' : '' }}">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 11 12 14 22 4"/>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
          </svg>
        </span>
        <span>Fabric Checking</span>
      </a>

      <a href="{{ route('dispatch.index') }}" class="{{ request()->routeIs('dispatch.*') ? 'active' : '' }}">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="3" width="15" height="13" rx="1"/>
            <path d="M16 8h4l3 5v3h-7V8z"/>
            <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
          </svg>
        </span>
        <span>Kachi / Pakki</span>
      </a>

      <a href="{{ route('accounts.index') }}" class="{{ request()->routeIs('accounts.*') ? 'active' : '' }}">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
          </svg>
        </span>
        <span>Accounts &amp; Bills</span>
      </a>

      <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
            <line x1="6" y1="20" x2="6" y2="14"/>
          </svg>
        </span>
        <span>Reports</span>
      </a>

    </nav>

    {{-- Admin nav --}}
    <div class="side-caption">ADMINISTRATION</div>
    <nav>

      <a href="{{ route('setup.index') }}" class="{{ request()->routeIs('setup.*') ? 'active' : '' }}">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
            <path d="M12 2v2m0 16v2M2 12h2m16 0h2"/>
          </svg>
        </span>
        <span>Settings &amp; Masters</span>
      </a>

      <a href="{{ route('security.password') }}" class="{{ request()->routeIs('security.password*') ? 'active' : '' }}">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
        </span>
        <span>My Password</span>
      </a>

      @if(auth()->user()?->role === 'admin')
      <a href="{{ route('security.index') }}" class="{{ request()->routeIs('security.index') ? 'active' : '' }}">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </span>
        <span>Users &amp; Audit</span>
      </a>
      @endif

    </nav>

    <div class="side-note">Internal system · Authorized use only</div>

  </aside>

  <main class="main-content">

    <header class="topbar">
      <div class="business-context">
        <span class="context-mark">W</span>
        <div>
          <strong>One Business</strong>
          <small>Three Factories</small>
        </div>
      </div>
      <div class="topbar-right">
        <div class="user-chip">
          <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
          <span class="user-details">
            <strong>{{ auth()->user()->name }}</strong>
            <small>{{ ucfirst(auth()->user()->role) }}</small>
          </span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn secondary small">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Sign Out
          </button>
        </form>
      </div>
    </header>

    <div class="page-content">
      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error">
          <strong>Some information needs attention:</strong>
          <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif
      @yield('content')
    </div>

  </main>
</div>
@stack('scripts')
</body>
</html>
