@extends('layouts.app')
@section('title','Available Fabric Stock')
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Inventory</div>
    <h1>Available Fabric Stock</h1>
    <p class="tagline">Only checked Thaans not currently dispatched or accepted.</p>
  </div>
  <a class="btn" href="{{ route('fabric.index') }}">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:7px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Fabric Checking
  </a>
</div>

{{-- ── Filter + Export bar ── --}}
<div class="form-section-card" style="margin-bottom:24px;padding:18px 22px">
  <div class="stock-filter-bar">
    <form method="GET" style="display:flex;align-items:center;gap:10px;flex:1;flex-wrap:wrap">
      <div class="field" style="margin:0;flex:1;min-width:200px">
        <select name="factory_id">
          <option value="">All Factories</option>
          @foreach($factories as $f)
            <option value="{{ $f->id }}" @selected(request('factory_id') == $f->id)>{{ $f->name }}</option>
          @endforeach
        </select>
      </div>
      <button class="btn secondary" type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Filter
      </button>
    </form>
    <a class="btn secondary small" href="{{ route('export.stock', request()->only('factory_id')) }}">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </a>
  </div>
</div>

{{-- ── Stock table ── --}}
<div class="card">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Available Thaans</h2>
      <p class="muted small" style="margin:4px 0 0">Each row is one checked Thaan lot ready for dispatch.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Thaan Number</th>
          <th>Factory</th>
          <th>Quality</th>
          <th>Ownership</th>
          <th>Contract</th>
          <th class="num">Metres</th>
        </tr>
      </thead>
      <tbody>
        @forelse($thaans as $t)
        <tr>
          <td><span class="ref-link">{{ $t->thaan_no }}</span></td>
          <td>{{ $t->factory->name }}</td>
          <td>{{ $t->quality->name }}</td>
          <td>
            <span class="pill {{ $t->ownership === 'own' ? 'dark' : '' }}">
              {{ $t->ownership === 'own' ? 'Factory Owned' : 'Job Work' }}
            </span>
          </td>
          <td>
            @if($t->contract)
              <a href="{{ route('contracts.show', $t->contract) }}" class="ref-link">{{ $t->contract->reference }}</a>
            @else
              <span class="muted small">For Stock</span>
            @endif
          </td>
          <td class="num"><strong>{{ number_format($t->metres, 2) }}</strong></td>
        </tr>
        @empty
        <tr><td colspan="6" class="empty">No available Thaans recorded.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($thaans->hasPages())
  <div style="padding:16px 0 4px">{{ $thaans->links() }}</div>
  @endif
</div>

@endsection
