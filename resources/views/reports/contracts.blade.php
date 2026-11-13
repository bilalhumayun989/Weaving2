@extends('layouts.app')
@section('title','Contract Ledger')
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Management Information</div>
    <h1>Contract Ledger</h1>
    <p class="tagline">Tracks every sale dispatched against a contract. Contract creation records agreed terms; sales post when fabric is dispatched.</p>
  </div>
  <a class="btn secondary small" href="{{ route('export.sales', request()->query()) }}">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export CSV
  </a>
</div>

{{-- ── Tab navigation ── --}}
@include('reports._navigation')

{{-- ── Filters ── --}}
<div class="form-section-card" style="margin-bottom:24px;padding:20px 24px">
  <div class="form-section-head" style="margin-bottom:18px;padding-bottom:14px">
    <div class="form-section-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
    </div>
    <div>
      <div class="form-section-title">Filter Contract Sales</div>
      <div class="form-section-sub">Narrow down by date range, factory, customer, contract or billing status</div>
    </div>
  </div>
  <form method="GET" action="{{ route('reports.contracts') }}">
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
      <a class="btn secondary" href="{{ route('reports.contracts') }}">Clear</a>
    </div>
  </form>
</div>

{{-- ── Sales Totals by Contract ── --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Sales Totals by Contract</h2>
      <p class="muted small" style="margin:4px 0 0">Active sales total is provisional plus final sales; customer payments are recorded separately in each account ledger.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Contract</th>
          <th>Customer</th>
          <th>Factory Scope</th>
          <th class="num">Provisional (PKR)</th>
          <th class="num">Final (PKR)</th>
          <th class="num">Voided (PKR)</th>
          <th class="num">Active Total (PKR)</th>
        </tr>
      </thead>
      <tbody>
        @forelse($contractSales as $row)
        <tr>
          <td><a href="{{ route('contracts.show', $row->contract) }}" class="ref-link">{{ $row->contract->reference }}</a></td>
          <td>{{ $row->contract->party->name }}</td>
          <td>
            @if(($row->contract->factory_scope ?? 'single') === 'all')
              <span class="pill dark">All Factories</span>
            @else
              {{ $row->contract->factory->name }}
            @endif
          </td>
          <td class="num">{{ number_format((float)$row->provisional_total, 2) }}</td>
          <td class="num">{{ number_format((float)$row->final_total, 2) }}</td>
          <td class="num muted">{{ number_format((float)$row->void_total, 2) }}</td>
          <td class="num"><strong>{{ number_format((float)$row->receivable_total, 2) }}</strong></td>
        </tr>
        @empty
        <tr><td colspan="7" class="empty">No contract sales match these filters. A contract appears after its first fabric dispatch.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ── Contract Sale Entries ── --}}
<div class="card">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Contract Sale Entries</h2>
      <p class="muted small" style="margin:4px 0 0">Individual bill entries grouped by contract and billing status.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Bill / Kachi</th>
          <th>Contract</th>
          <th>Customer</th>
          <th>Factory</th>
          <th>Status</th>
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
          <td><a href="{{ route('contracts.show', $sale->contract) }}" class="ref-link">{{ $sale->contract->reference }}</a></td>
          <td>{{ $sale->party->name }}</td>
          <td>{{ $sale->factory->name }}</td>
          <td>
            <span class="status-pill status-{{ $sale->billing_status }}">{{ ucfirst($sale->billing_status) }}</span>
          </td>
          <td>
            <span class="pill {{ $sale->type === 'conversion' ? '' : 'dark' }}">
              {{ $sale->type === 'conversion' ? 'Job Work' : 'Fabric Sale' }}
            </span>
          </td>
          <td class="num">{{ number_format($sale->accepted_metres, 2) }}</td>
          <td class="num">Rs {{ number_format($sale->type === 'conversion' && $sale->contract->yarn_supplier !== 'factory' ? (float)$sale->picks * (float)$sale->rate : (float)$sale->rate, 4) }}</td>
          <td class="num"><strong>{{ number_format($sale->billing_status === 'void' ? 0 : $sale->total, 2) }}</strong></td>
        </tr>
        @empty
        <tr><td colspan="10" class="empty">No contract sales match these filters.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($sales->hasPages())
  <div style="padding:16px 0 4px">{{ $sales->links() }}</div>
  @endif
</div>

@endsection
