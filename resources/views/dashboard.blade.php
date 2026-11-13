@extends('layouts.app')
@section('title','Dashboard')
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Overview</div>
    <h1>Business Dashboard</h1>
    <p class="tagline">A live view across all three factories.</p>
  </div>
  <a class="btn" href="{{ route('fabric.index') }}">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:7px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Enter Checked Fabric
  </a>
</div>

{{-- ── KPI metric cards ── --}}
<div class="grid grid-4" style="margin-bottom:24px">

  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--green">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
        <path d="M12 6v6l4 2"/>
      </svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Customer Receivables</div>
      <div class="dmc-value">Rs&nbsp;{{ number_format($receivables, 0) }}</div>
      <div class="dmc-sub">Combined party balances</div>
    </div>
  </div>

  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--amber">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="14" rx="2"/>
        <line x1="2" y1="10" x2="22" y2="10"/>
      </svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Supplier Payables</div>
      <div class="dmc-value">Rs&nbsp;{{ number_format($payables, 0) }}</div>
      <div class="dmc-sub">Combined supplier balances</div>
    </div>
  </div>

  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--teal">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
      </svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Available Fabric</div>
      <div class="dmc-value">{{ number_format($availableMetres, 2) }}&nbsp;m</div>
      <div class="dmc-sub">{{ $availableThaans }} available Thaans</div>
    </div>
  </div>

  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--brand">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="8" y1="13" x2="16" y2="13"/>
        <line x1="8" y1="17" x2="16" y2="17"/>
      </svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Active Contracts</div>
      <div class="dmc-value">{{ $activeContracts }}</div>
      <div class="dmc-sub">{{ $factoryCount }} factories configured</div>
    </div>
  </div>

</div>

{{-- ── Quick actions + Attention ── --}}
<div class="grid" style="margin-bottom:24px">

  <div class="card">
    <h2>Quick Actions</h2>
    <p class="muted small" style="margin-bottom:16px">Jump straight to any module.</p>
    <div class="dash-action-grid">

      <a class="dash-action-btn" href="{{ route('contracts.create') }}">
        <span class="dab-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
        </span>
        <span class="dab-label">New Contract</span>
      </a>

      <a class="dash-action-btn" href="{{ route('yarn.index') }}">
        <span class="dab-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            <line x1="2" y1="12" x2="22" y2="12"/>
          </svg>
        </span>
        <span class="dab-label">Yarn Purchase</span>
      </a>

      <a class="dash-action-btn" href="{{ route('fabric.index') }}">
        <span class="dab-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 11 12 14 22 4"/>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
          </svg>
        </span>
        <span class="dab-label">Fabric Checking</span>
      </a>

      <a class="dash-action-btn" href="{{ route('dispatch.index') }}">
        <span class="dab-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="3" width="15" height="13" rx="1"/>
            <path d="M16 8h4l3 5v3h-7V8z"/>
            <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
          </svg>
        </span>
        <span class="dab-label">Kachi / Pakki</span>
      </a>

      <a class="dash-action-btn" href="{{ route('accounts.index') }}">
        <span class="dab-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
          </svg>
        </span>
        <span class="dab-label">Receive Payment</span>
      </a>

    </div>
    <p class="inline-note" style="margin-top:16px;margin-bottom:0">Stock comes directly from checked Thaan measurements — no production-entry step needed.</p>
  </div>

  <div class="card">
    <h2>Attention Needed</h2>
    <p class="muted small" style="margin-bottom:20px">Items pending action across the workflow.</p>
    <div class="dash-attention-grid">

      <div class="dash-attention-item">
        <div class="dai-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
        <div>
          <div class="dai-value">{{ $awaitingPakki }}</div>
          <div class="dai-label">Kachis awaiting Pakki</div>
        </div>
      </div>

      <div class="dash-attention-item">
        <div class="dai-icon dai-icon--warn">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
        </div>
        <div>
          <div class="dai-value">{{ $pendingReturns }}</div>
          <div class="dai-label">Pakki awaiting Thaan returns</div>
        </div>
      </div>

    </div>
  </div>

</div>

{{-- ── Supplier Payments Due ── --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row">
    <div>
      <h2 style="margin-bottom:4px">Supplier Payments Due</h2>
      <p class="muted small" style="margin:0">Overdue &amp; payments due within the next 7 days.</p>
    </div>
    <a class="btn secondary small" href="{{ route('accounts.index') }}">View All</a>
  </div>
  <div class="table-wrap" style="margin-top:18px">
    <table>
      <thead>
        <tr>
          <th>Due Date</th>
          <th>Supplier / Purchase</th>
          <th>Factory</th>
          <th>Status</th>
          <th class="num">Outstanding (PKR)</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($dueYarnPurchases as $purchase)
        <tr>
          <td>{{ $purchase->payment_due_on }}</td>
          <td>
            {{ $purchase->party->name }}
            <div class="small muted">{{ $purchase->invoice_no ?: 'Purchase #'.$purchase->id }} &middot; {{ $purchase->payment_terms_days }}&nbsp;day terms</div>
          </td>
          <td>{{ $purchase->factory->name }}</td>
          <td>
            @if($purchase->is_overdue)
              <span class="pill warn">Overdue &nbsp;{{ $purchase->days_overdue }}d</span>
            @elseif($purchase->days_remaining === 0)
              <span class="pill warn">Due today</span>
            @else
              <span class="pill">Due in {{ $purchase->days_remaining }}d</span>
            @endif
          </td>
          <td class="num">{{ number_format($purchase->outstanding_amount, 2) }}</td>
          <td>
            <a class="btn secondary small" href="{{ route('accounts.show', [$purchase->party, 'yarn_purchase_id' => $purchase->id]) }}">Record Payment</a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="empty">No supplier yarn payments are due in the next 7 days.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ── Recent Bills ── --}}
<div class="card">
  <div class="card-header-row">
    <div>
      <h2 style="margin-bottom:4px">Recent Bills</h2>
      <p class="muted small" style="margin:0">Latest generated bills across all parties.</p>
    </div>
    <a class="btn secondary small" href="{{ route('accounts.index') }}">View All</a>
  </div>
  <div class="table-wrap" style="margin-top:18px">
    <table>
      <thead>
        <tr>
          <th>Bill Ref</th>
          <th>Party</th>
          <th>Factory</th>
          <th>Type</th>
          <th class="num">Amount (PKR)</th>
        </tr>
      </thead>
      <tbody>
        @forelse($recentBills as $bill)
        <tr>
          <td>
            <a href="{{ route('print.bill', $bill) }}" target="_blank" style="font-weight:600">{{ $bill->reference }}</a>
          </td>
          <td>{{ $bill->party->name }}</td>
          <td>{{ $bill->factory->name }}</td>
          <td><span class="pill dark">{{ ucfirst(str_replace('_', ' ', $bill->type)) }}</span></td>
          <td class="num">{{ number_format($bill->total, 2) }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="5" class="empty">No bills yet.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
