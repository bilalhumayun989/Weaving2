@extends('layouts.app')
@section('title','Contract · ' . $contract->reference)
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Contract Details</div>
    <h1>{{ $contract->reference }}</h1>
    <p class="tagline">{{ $contract->party->name }} &middot; {{ ($contract->factory_scope ?? 'single') === 'all' ? 'All Factories' : $contract->factory->name }}</p>
  </div>
  <div class="actions">
    <a class="btn" href="{{ route('print.contract', $contract) }}" target="_blank">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print Contract
    </a>
    <a class="btn secondary" href="{{ route('contracts.index') }}">All Contracts</a>
  </div>
</div>

{{-- ── Summary metric cards ── --}}
<div class="grid grid-3" style="margin-bottom:24px">
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--brand">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Contract Type</div>
      <div class="dmc-value" style="font-size:18px">{{ $contract->type === 'job_work' ? 'Job Work' : 'Fabric Rate' }}</div>
      <div class="dmc-sub">{{ $contract->quality->name }}</div>
    </div>
  </div>
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--green">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Agreed Rate</div>
      <div class="dmc-value">Rs {{ number_format($contract->type === 'job_work' ? $contract->rate_per_pick : $contract->rate_per_metre, 4) }}</div>
      <div class="dmc-sub">{{ $contract->type === 'job_work' ? 'Per pick' : 'Per metre' }}</div>
    </div>
  </div>
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--teal">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Target Quantity</div>
      <div class="dmc-value">{{ $contract->target_metres ? number_format($contract->target_metres, 2).' m' : '—' }}</div>
      <div class="dmc-sub">
        <span class="status-pill status-{{ $contract->status }}">{{ ucfirst($contract->status) }}</span>
      </div>
    </div>
  </div>
</div>

{{-- ── Sales Ledger ── --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:18px">
    <div>
      <h2>Contract Sales Ledger</h2>
      <p class="muted small" style="margin:4px 0 0">Only dispatched fabric creates a sale. Provisional Kachi entries are finalized on the same bill when Pakki is recorded.</p>
    </div>
    <a class="btn secondary small" href="{{ route('reports.contracts', ['contract_id' => $contract->id]) }}">Open Ledger</a>
  </div>
  <div class="grid grid-3" style="margin-bottom:18px">
    <div class="ledger-stat">
      <div class="ledger-stat-label">Provisional</div>
      <div class="ledger-stat-value">Rs {{ number_format($provisionalSales, 2) }}</div>
    </div>
    <div class="ledger-stat">
      <div class="ledger-stat-label">Final</div>
      <div class="ledger-stat-value">Rs {{ number_format($finalSales, 2) }}</div>
    </div>
    <div class="ledger-stat">
      <div class="ledger-stat-label">Active Sales Total</div>
      <div class="ledger-stat-value">Rs {{ number_format($provisionalSales + $finalSales, 2) }}</div>
      <div class="ledger-stat-sub">Voided: Rs {{ number_format($voidSales, 2) }}</div>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th><th>Bill</th><th>Factory</th><th>Status</th>
          <th class="num">Metres</th><th class="num">Rate / Metre</th><th class="num">Amount (PKR)</th>
        </tr>
      </thead>
      <tbody>
        @forelse($salesBills as $bill)
        <tr>
          <td>{{ $bill->billed_on }}</td>
          <td><a href="{{ route('print.bill', $bill) }}" target="_blank" style="font-weight:600">{{ $bill->reference }}</a></td>
          <td>{{ $bill->factory->name }}</td>
          <td><span class="status-pill status-{{ $bill->billing_status }}">{{ ucfirst($bill->billing_status) }}</span></td>
          <td class="num">{{ number_format($bill->accepted_metres, 2) }}</td>
          <td class="num">Rs {{ number_format($bill->type === 'conversion' && $contract->yarn_supplier !== 'factory' ? (float)$bill->picks * (float)$bill->rate : (float)$bill->rate, 4) }}</td>
          <td class="num">{{ number_format($bill->billing_status === 'void' ? 0 : $bill->total, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="empty">No fabric dispatched against this contract yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if($contract->type === 'job_work')

{{-- ── Yarn Bag Summary ── --}}
<div class="grid grid-3" style="margin-bottom:24px">
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--teal">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Calculated Yarn Requirement</div>
      <div class="dmc-value">{{ number_format($contract->required_bags, 2) }} bags</div>
      <div class="dmc-sub">Estimated from Parta &middot; maximum allowed</div>
    </div>
  </div>
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--brand">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Yarn Supplied By</div>
      <div class="dmc-value" style="font-size:18px">{{ $contract->yarn_supplier === 'customer' ? 'Customer / Party' : 'Factory' }}</div>
    </div>
  </div>
  @if($contract->yarn_supplier === 'customer')
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--green">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Customer Bags Received / Remaining</div>
      <div class="dmc-value">{{ number_format($received, 2) }} / {{ number_format($remaining, 2) }}</div>
      <div class="dmc-sub">
        @if($received > (float)$contract->required_bags) Over supplied: {{ number_format($received - (float)$contract->required_bags, 2) }}
        @elseif($remaining <= 0) Required amount fully received.
        @endif
      </div>
    </div>
  </div>
  @endif
</div>

{{-- Warp / Weft breakdown --}}
<div class="grid grid-3" style="margin-bottom:24px">
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--amber">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Warp Bags Received / Required</div>
      <div class="dmc-value">{{ number_format($warpReceived, 2) }} / {{ number_format($warpRequired, 2) }}</div>
    </div>
  </div>
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--amber">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="5 12 12 19 19 12"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Weft Bags Received / Required</div>
      <div class="dmc-value">{{ number_format($weftReceived, 2) }} / {{ number_format($weftRequired, 2) }}</div>
    </div>
  </div>
  @if($contract->yarn_supplier === 'factory')
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--teal">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Supplier Yarn Remaining</div>
      <div class="dmc-value">{{ number_format($remaining, 2) }} bags</div>
      <div class="dmc-sub">Tracked against Warp and Weft purchases</div>
    </div>
  </div>
  @endif
</div>

{{-- ── Parta Snapshot ── --}}
@if($contract->parta_snapshot)
@php($p = $contract->parta_snapshot)
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <h2>Approved Fabric Costing / Parta</h2>
  </div>
  <div class="note" style="margin-bottom:16px">Calculation saved when this contract was created. The fixed bag weight is <strong>100 lbs</strong> regardless of cone count. This costing estimate does not post to supplier accounts or COGS.</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Detail</th><th class="num">Warp</th><th class="num">Weft</th></tr></thead>
      <tbody>
        <tr><td>Yarn Count</td><td class="num">{{ number_format($p['inputs']['warp_count'], 3) }}</td><td class="num">{{ number_format($p['inputs']['weft_count'], 3) }}</td></tr>
        <tr><td>Weight / Metre (lbs)</td><td class="num">{{ number_format($p['results']['warp_lbs_per_metre'], 4) }}</td><td class="num">{{ number_format($p['results']['weft_lbs_per_metre'], 4) }}</td></tr>
        <tr><td>Required Bags</td><td class="num"><strong>{{ number_format($p['results']['warp_bags'], 2) }}</strong></td><td class="num"><strong>{{ number_format($p['results']['weft_bags'], 2) }}</strong></td></tr>
        @if($p['results']['total_yarn_cost_per_metre'] !== null)
        <tr><td>Yarn Cost / Metre (Rs)</td><td class="num">{{ number_format($p['results']['warp_yarn_cost_per_metre'], 4) }}</td><td class="num">{{ number_format($p['results']['weft_yarn_cost_per_metre'], 4) }}</td></tr>
        @endif
      </tbody>
    </table>
  </div>
  <p class="inline-note" style="margin-top:14px">Quantity: {{ number_format($p['inputs']['quantity_metres'], 2) }} m &middot; Reed: {{ $p['inputs']['reed'] }} &middot; Picks: {{ $p['inputs']['picks'] }} &middot; Width: {{ $p['inputs']['width'] }} inches</p>
  <div class="grid grid-3" style="margin-top:16px">
    <div class="ledger-stat"><div class="ledger-stat-label">Total required bags</div><div class="ledger-stat-value">{{ number_format($p['results']['total_required_bags'], 2) }}</div></div>
    <div class="ledger-stat"><div class="ledger-stat-label">Conversion / metre</div><div class="ledger-stat-value">Rs {{ number_format($p['results']['conversion_per_metre'], 4) }}</div></div>
    <div class="ledger-stat"><div class="ledger-stat-label">Est. fabric cost / metre</div><div class="ledger-stat-value">{{ $p['results']['estimated_fabric_rate_per_metre'] !== null ? 'Rs '.number_format($p['results']['estimated_fabric_rate_per_metre'], 4) : '— (yarn rates not supplied)' }}</div></div>
  </div>
</div>
@endif

@if($contract->parta_details)
<div class="card" style="margin-bottom:24px">
  <h2>Parta / Contract Remarks</h2>
  <pre style="white-space:pre-wrap;font:inherit;margin:12px 0 0;color:var(--ink)">{{ $contract->parta_details }}</pre>
</div>
@endif

@if($contract->type === 'job_work' && $contract->status === 'active')
<div class="note" style="margin-bottom:24px">Record customer-supplied bags and supplier yarn purchases from <a href="{{ route('yarn.index') }}">Yarn Purchases</a>. The selected contract will set the owner and factory scope automatically.</div>
@endif

@if($contract->yarn_supplier === 'customer')
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <div><h2>Customer Yarn Receipt History</h2></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Warp / Weft</th><th>Factory Scope</th><th class="num">Bags</th><th class="num">Cones / Bag</th><th>Delivery Reference</th></tr></thead>
      <tbody>
        @forelse($receipts as $r)
        <tr>
          <td>{{ $r->received_on }}</td>
          <td>{{ $r->yarn_type ? ucfirst($r->yarn_type) : 'Unclassified' }}</td>
          <td>{{ ($r->factory_scope ?? 'single') === 'all' ? 'All Factories' : ($r->factory?->name ?? $contract->factory->name) }}</td>
          <td class="num">{{ number_format($r->bags, 3) }}</td>
          <td class="num">{{ $r->cones_per_bag ?? '—' }}</td>
          <td>{{ $r->reference ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="empty">No customer bags received yet. Record them from Yarn Purchases.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@else
<div class="card" style="margin-bottom:24px">
  <div class="note">Factory purchases yarn in the Yarn section. Fabric is recorded directly through checking; no production entry required.</div>
  <div style="margin-top:14px"><a class="btn secondary" href="{{ route('yarn.index') }}">Open Yarn Purchases</a></div>
</div>
@endif

@if($contract->yarn_supplier === 'factory')
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px"><h2>Supplier Yarn Delivery History</h2></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Supplier</th><th>Warp / Weft</th><th>Description</th><th class="num">Bags</th><th class="num">Rate / Bag</th><th>Payment Terms / Due</th><th class="num">Payable</th></tr></thead>
      <tbody>
        @forelse($purchases as $purchase)
        <tr>
          <td>{{ $purchase->purchased_on }}</td>
          <td>{{ $purchase->party->name }}</td>
          <td>{{ ucfirst($purchase->yarn_type) }}</td>
          <td>{{ $purchase->yarnQuality?->name ?? $purchase->yarn_description }}{{ $purchase->yarnQuality?->count ? ' · '.$purchase->yarnQuality->count : '' }}</td>
          <td class="num">{{ number_format($purchase->bags, 2) }}</td>
          <td class="num">Rs {{ number_format($purchase->rate_per_bag, 4) }}</td>
          <td>{{ $purchase->payment_due_on ? $purchase->payment_terms_days.' days · '.$purchase->payment_due_on : 'Not specified' }}
            @if($purchase->payment_terms_note)<div class="small muted">{{ $purchase->payment_terms_note }}</div>@endif
          </td>
          <td class="num">Rs {{ number_format($purchase->total, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="8" class="empty">No supplier deliveries recorded for this contract.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endif

@if($contract->yarn_supplier === 'customer' && $purchases->isNotEmpty())
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:12px"><h2>Factory Yarn Purchases Linked to this Contract</h2></div>
  <p class="hint">Factory supplier purchases earmarked for this contract. They stay separate from customer bag requirements and do not reduce that receipt requirement.</p>
  <div class="table-wrap" style="margin-top:12px">
    <table>
      <thead><tr><th>Date</th><th>Supplier</th><th>Warp / Weft</th><th>Yarn Quality</th><th class="num">Bags</th><th class="num">Rate / Bag</th><th class="num">Payable</th></tr></thead>
      <tbody>
        @foreach($purchases as $purchase)
        <tr>
          <td>{{ $purchase->purchased_on }}</td>
          <td>{{ $purchase->party->name }}</td>
          <td>{{ ucfirst($purchase->yarn_type) }}</td>
          <td>{{ $purchase->yarnQuality?->name ?? $purchase->yarn_description }}{{ $purchase->yarnQuality?->count ? ' · '.$purchase->yarnQuality->count : '' }}</td>
          <td class="num">{{ number_format($purchase->bags, 3) }}</td>
          <td class="num">Rs {{ number_format($purchase->rate_per_bag, 4) }}</td>
          <td class="num">Rs {{ number_format($purchase->total, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@endif {{-- end job_work --}}

{{-- ── Close Contract ── --}}
@if($contract->status === 'active' && auth()->user()->role === 'admin')
<div class="card">
  <div class="card-header-row" style="margin-bottom:12px">
    <div>
      <h2>Close Contract</h2>
      <p class="muted small" style="margin:4px 0 0">Closure is only allowed when no unsent fabric or unresolved Kachi/Pakki remains. This does not create any unagreed financial settlement.</p>
    </div>
  </div>
  <form method="POST" action="{{ route('contracts.close', $contract) }}" onsubmit="return confirm('Close contract permanently for new activity?')">
    @csrf
    <div class="form-grid">
      <div class="field"><label>Closure Date</label><input type="date" name="closed_on" value="{{ date('Y-m-d') }}" required></div>
      <div class="field"><label>Reason</label><input name="close_reason" required minlength="8" placeholder="Contract completed / settled"></div>
    </div>
    <div class="input-actions"><button class="btn secondary">Close Contract</button></div>
  </form>
</div>
@endif

@endsection
