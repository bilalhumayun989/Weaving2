@extends('layouts.app')
@section('title','Bills & Fabric Sales')
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Accounts</div>
    <h1>Bills &amp; Fabric Sales</h1>
    <p class="tagline">Kachi bills appear as provisional entries; Pakki updates each bill and receivable to the final accepted amount.</p>
  </div>
  <div class="actions">
    <a class="btn secondary" href="{{ route('export.transactions', ['kind' => 'bills']) }}">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export All CSV
    </a>
    <a class="btn secondary" href="{{ route('accounts.index') }}">All Accounts</a>
  </div>
</div>

{{-- ── Filter bar ── --}}
<div class="form-section-card" style="margin-bottom:24px;padding:18px 22px">
  <form method="GET" action="{{ route('accounts.bills') }}">
    <div class="bills-filter-bar">
      <div class="field" style="margin:0;flex:1;min-width:160px">
        <label>Party</label>
        <select name="party_id">
          <option value="">All parties</option>
          @foreach($parties as $p)
            <option value="{{ $p->id }}" @selected(request('party_id') == $p->id)>{{ $p->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field" style="margin:0;flex:1;min-width:160px">
        <label>Factory</label>
        <select name="factory_id">
          <option value="">All factories</option>
          @foreach($factories as $f)
            <option value="{{ $f->id }}" @selected(request('factory_id') == $f->id)>{{ $f->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field" style="margin:0;flex:1;min-width:160px">
        <label>Billing Type</label>
        <select name="type">
          <option value="">Both</option>
          <option value="conversion"   @selected(request('type') === 'conversion')>Job Work Bill</option>
          <option value="fabric_sale"  @selected(request('type') === 'fabric_sale')>Own Fabric Sale</option>
        </select>
      </div>
      <div class="field" style="margin:0;align-self:flex-end">
        <button class="btn secondary" type="submit">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Apply Filters
        </button>
      </div>
    </div>
  </form>
</div>

{{-- ── Bills table ── --}}
<div class="card">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>All Bills</h2>
      <p class="muted small" style="margin:4px 0 0">Provisional bills update to final amounts when Pakki is recorded.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Bill Ref</th>
          <th>Date</th>
          <th>Party</th>
          <th>Factory</th>
          <th>Type</th>
          <th class="num">Metres</th>
          <th class="num">Total (PKR)</th>
          <th>Print</th>
        </tr>
      </thead>
      <tbody>
        @forelse($bills as $b)
        <tr>
          <td><span class="ref-link">{{ $b->reference }}</span></td>
          <td>{{ $b->billed_on }}</td>
          <td>
            <a href="{{ route('accounts.show', $b->party) }}" class="ref-link">{{ $b->party->name }}</a>
          </td>
          <td>{{ $b->factory->name }}</td>
          <td>
            <span class="pill {{ $b->type === 'fabric_sale' ? 'dark' : '' }}">
              {{ $b->type === 'fabric_sale' ? 'Fabric Sale' : ($b->contract?->yarn_supplier === 'factory' ? 'Job Work · Factory Yarn' : 'Job Work · Customer Yarn') }}
            </span>
          </td>
          <td class="num">
            {{ number_format($b->accepted_metres, 2) }}
            @if($b->billing_status === 'provisional')
              <div class="small muted">Provisional</div>
            @endif
          </td>
          <td class="num">
            <strong>{{ number_format($b->total, 2) }}</strong>
            <div style="margin-top:3px">
              <span class="status-pill status-{{ $b->billing_status }}">{{ ucfirst($b->billing_status) }}</span>
            </div>
          </td>
          <td>
            <a class="btn secondary small" href="{{ route('print.bill', $b) }}" target="_blank">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
              Print
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" class="empty">No bills generated yet. Create a Kachi Parchi to post provisional billing.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($bills->hasPages())
  <div style="padding:16px 0 4px">{{ $bills->links() }}</div>
  @endif
</div>

@endsection
