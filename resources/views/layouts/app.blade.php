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
    <div class="brand"><div class="logo">W</div><div><strong>Weaving ERP</strong><small>BroshTech</small></div></div>
    <div class="side-caption">WORKSPACE</div>
    <nav>
      <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">⌂ <span>Dashboard</span></a>
      <a href="{{ route('contracts.index') }}" class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}">▤ <span>Contracts</span></a>
      <a href="{{ route('yarn.index') }}" class="{{ request()->routeIs('yarn.*') ? 'active' : '' }}">▦ <span>Yarn</span></a>
      <a href="{{ route('fabric.index') }}" class="{{ request()->routeIs('fabric.*') ? 'active' : '' }}">▧ <span>Fabric Checking</span></a>
      <a href="{{ route('dispatch.index') }}" class="{{ request()->routeIs('dispatch.*') ? 'active' : '' }}">⇢ <span>Kachi / Pakki</span></a>
      <a href="{{ route('accounts.index') }}" class="{{ request()->routeIs('accounts.*') ? 'active' : '' }}">◫ <span>Accounts & Bills</span></a>
      <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">▥ <span>Reports</span></a>
    </nav>
    <div class="side-caption">ADMINISTRATION</div>
    <nav><a href="{{ route('setup.index') }}" class="{{ request()->routeIs('setup.*') ? 'active' : '' }}">⚙ <span>Settings & Masters</span></a></nav>
    <nav><a href="{{ route('security.password') }}" class="{{ request()->routeIs('security.password*') ? 'active' : '' }}">♙ <span>My Password</span></a>
@if(auth()->user()?->role==='admin')<a href="{{ route('security.index') }}" class="{{ request()->routeIs('security.index') ? 'active' : '' }}">♙ <span>Users & Audit</span></a>@endif</nav>
  </aside>
  <main class="main-content">
    <header class="topbar"><div class="business-context"><span class="context-mark">W</span><div><strong>One Business</strong><small>Three Factories</small></div></div><div class="topbar-right"><div class="user-chip"><span class="user-avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span class="user-details"><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }}</small></span></div><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn secondary small">Sign Out</button></form></div></header>
    <div class="page-content">
      @if(session('success')) <div class="notice success">{{ session('success') }}</div> @endif
      @if($errors->any())<div class="notice error"><strong>Some information needs attention:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
      @yield('content')
    </div>
  </main>
</div>
@stack('scripts')
</body>
</html>
