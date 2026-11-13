@extends('layouts.app')
@section('title','Sales Report')
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Management Information</div>
    <h1>Sales Report</h1>
    <p class="tagline">Every Kachi dispatch creates a provisional sale and customer receivable. Pakki finalizes that same entry; cancelled Kachis are reversed.</p>
  </div>
  <a class="btn secondary small" href="{{ route('export.sales', request()->query()) }}">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export CSV
  </a>
</div>

{{-- ── Tab navigation ── --}}
@include('reports._navigation')

{{-- ── Summary metric cards ── --}}
<div class="grid grid-3" style="margin-bottom:24px">
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--amber">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Provisional Receivables</div>
      <div class="dmc-value">Rs&nbsp;{{ number_format((float)$summary->provisional_total, 2) }}</div>
      <div class="dmc-sub">Awaiting Pakki acceptance</div>
    </div>
  </div>
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--green">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Final Sales</div>
      <div class="dmc-value">Rs&nbsp;{{ number_format((float)$summary->final_total, 2) }}</div>
      <div class="dmc-sub">Pakki accepted amount</div>
    </div>
  </div>
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--brand">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Active Sales Total</div>
      <div class="dmc-value">Rs&nbsp;{{ number_format((float)$summary->receivable_sales_total, 2) }}</div>
      <div class="dmc-sub">{{ number_format((float)$summary->receivable_metres, 2) }} m &middot; voids excluded</div>
    </div>
  </div>
</div>

{{-- ── Filters ── --}}
<div class="form-section-card" style="margin-bottom:24px;padding:20px 24px">
  <div class="form-section-head" style="margin-bottom:18px;padding-bottom:14px">
    <div class="form-section-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
    </div>
    <div>
      <div class="form-section-title">Filter Sales</div>
      <div class="form-section-sub">Narrow down by date range, factory, customer, contract or type</div>
    </div>
  </div>
  <form method="GET" action="{{ route('reports.sales') }}">
    <div class="form-grid">
      <div class="field"><label>From</label><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
      <div class="field"><label>To</label><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
      <div class="field">
        <label>Factory</label>
        <select name="factory_id">
          <option value="">All factories</option>
          @foreach($factories as $f)<option value="{{ $f->id }}" @selected(($filters['factory_id'] ?? '') == $f->id)>{{ $f->name }}</option>@endforeach
        </select>
      </div>
      <div class="field">
        <label>Customer</label>
        <select name="party_id">
          <option value="">All customers</option>
          @foreach($parties as $p)<option value="{{ $p->id }}" @selected(($filters['party_id'] ?? '') == $p->id)>{{ $p->name }}</option>@endforeach
        </select>
      </div>
      <div class="field">
        <label>Contract</label>
        <select name="contract_id">
          <option value="">All contracts</option>
          @foreach($contracts as $c)<option value="{{ $c->id }}" @selected(($filters['contract_id'] ?? '') == $c->id)>{{ $c->reference }} · {{ $c->party->name }}</option>@endforeach
        </select>
      </div>
      <div class="field">
        <label>Sale Type</label>
        <select name="type">
          <option value="">All types</option>
          <option value="conversion"  @selected(($filters['type'] ?? '') === 'conversion')>Job Work</option>
          <option value="fabric_sale" @selected(($filters['type'] ?? '') === 'fabric_sale')>Fabric Sale</option>
        </select>
      </div>
      <div class="field">
        <label>Billing Status</label>
        <select name="status">
          <option value="">All statuses</option>
          <option value="provisional" @selected(($filters['status'] ?? '') === 'provisional')>Provisional</option>
          <option value="final"       @selected(($filters['status'] ?? '') === 'final')>Final</option>
          <option value="void"        @selected(($filters['status'] ?? '') === 'void')>Void</option>
        </select>
      </div>
    </div>
    <div class="form-submit-bar" style="padding-top:14px">
      <button class="btn" type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Apply Filters
      </button>
      <a class="btn secondary" href="{{ route('reports.sales') }}">Clear</a>
    </div>
  </form>
</div>

{{-- ── Sale Sheet ── --}}
<div class="card">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Sale Sheet</h2>
      <p class="muted small" style="margin:4px 0 0">All dispatched and finalized fabric sales matching the current filters.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Bill / Kachi</th>
          <th>Status</th>
          <th>Customer</th>
          <th>Factory</th>
          <th>Contract</th>
          <th>Type</th>
          <th class="num">Metres</th>
          <th class="num">Rate / m</th>
          <th class="num">Amount (PKR)</th>
        </tr>
      </thead>
      <tbody>
        @forelse($sales as $sale)
        <tr>
          <td>{{ $sale->billed_on }}</td>
          <td>
            <a href="{{ route('print.bill', $sale) }}" target="_blank" class="ref-link">{{ $sale->reference }}</a>
            @if($sale->kachi)<div class="small muted">{{ $sale->kachi->reference }}</div>@endif
          </td>
          <td>
            <span class="status-pill status-{{ $sale->billing_status }}">{{ ucfirst($sale->billing_status) }}</span>
          </td>
          <td>{{ $sale->party->name }}</td>
          <td>{{ $sale->factory->name }}</td>
          <td>
            @if($sale->contract)
              <a href="{{ route('contracts.show', $sale->contract) }}" class="ref-link">{{ $sale->contract->reference }}</a>
            @else
              <span class="muted small">Stock sale</span>
            @endif
          </td>
          <td>
            <span class="pill {{ $sale->type === 'conversion' ? '' : 'dark' }}">
              {{ $sale->type === 'conversion' ? 'Job Work' : 'Fabric Sale' }}
            </span>
          </td>
          <td class="num">{{ number_format($sale->accepted_metres, 2) }}</td>
          <td class="num">Rs {{ number_format($sale->type === 'conversion' && $sale->contract?->yarn_supplier !== 'factory' ? (float)$sale->picks * (float)$sale->rate : (float)$sale->rate, 4) }}</td>
          <td class="num"><strong>{{ number_format($sale->billing_status === 'void' ? 0 : $sale->total, 2) }}</strong></td>
        </tr>
        @empty
        <tr><td colspan="10" class="empty">No sales match these filters.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($sales->hasPages())
  <div style="padding:16px 0 4px">{{ $sales->links() }}</div>
  @endif
</div>

@endsection
