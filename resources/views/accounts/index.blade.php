@extends('layouts.app')
@section('title','Accounts')
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Finance</div>
    <h1>Customer &amp; Supplier Accounts</h1>
    <p class="tagline">Customer receivables and supplier payables across all factories.</p>
  </div>
  <div class="actions">
    <a class="btn secondary" href="{{ route('accounts.bills') }}">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      All Bills &amp; Sales
    </a>
    <a class="btn" href="#add-party">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Customer
    </a>
  </div>
</div>

{{-- ── Summary metric cards ── --}}
<div class="grid" style="margin-bottom:24px">
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--green">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Total Customer Receivables</div>
      <div class="dmc-value">Rs&nbsp;{{ number_format($receivables, 2) }}</div>
      <div class="dmc-sub">Combined party balances</div>
    </div>
  </div>
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--amber">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Total Supplier Payables</div>
      <div class="dmc-value">Rs&nbsp;{{ number_format($payables, 2) }}</div>
      <div class="dmc-sub">Combined supplier balances</div>
    </div>
  </div>
</div>

{{-- ── Without accounts notice ── --}}
@if($withoutAccounts)
<div class="note" style="margin-bottom:20px">
  {{ $withoutAccounts }} customer {{ $withoutAccounts === 1 ? 'has' : 'have' }} no account activity yet. Create an account for them from the party list below.
</div>
@endif

{{-- ── Customer Accounts ── --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Customer Accounts</h2>
      <p class="muted small" style="margin:4px 0 0">Sales bills increase what customers owe; payments received reduce it.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Customer</th>
          <th>Type</th>
          <th class="num">Receivable Balance (PKR)</th>
          <th class="num">Bills</th>
          <th class="num">Contracts</th>
          <th>Ledger</th>
        </tr>
      </thead>
      <tbody>
        @forelse($parties as $p)
        <tr class="table-row-link" onclick="window.location='{{ route('accounts.show', $p) }}'">
          <td><span class="ref-link">{{ $p->name }}</span></td>
          <td>
            <span class="pill {{ $p->type === 'both' ? 'dark' : '' }}">
              {{ $p->type === 'both' ? 'Customer & Supplier' : 'Customer' }}
            </span>
          </td>
          <td class="num">
            <span class="acct-balance {{ $p->balances['receivable'] > 0 ? 'acct-balance--positive' : '' }}">
              {{ number_format($p->balances['receivable'], 2) }}
            </span>
          </td>
          <td class="num">{{ $p->bills_count }}</td>
          <td class="num">{{ $p->contracts_count }}</td>
          <td>
            <a href="{{ route('accounts.show', $p) }}" class="btn secondary small">
              {{ $p->has_account_activity ? 'View Ledger' : 'Open Account' }}
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="empty">No customer accounts yet. Add a customer below.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ── Supplier Accounts ── --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Supplier Accounts</h2>
      <p class="muted small" style="margin:4px 0 0">Yarn purchases increase what you owe. Open a supplier ledger to review purchases and record payments.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Supplier</th>
          <th class="num">Yarn Purchases</th>
          <th class="num">Payable Balance (PKR)</th>
          <th>Ledger</th>
        </tr>
      </thead>
      <tbody>
        @forelse($suppliers as $supplier)
        <tr class="table-row-link" onclick="window.location='{{ route('accounts.show', $supplier) }}'">
          <td>
            <span class="ref-link">{{ $supplier->name }}</span>
            @if($supplier->type === 'both')
              <div class="small muted">Customer &amp; Supplier</div>
            @endif
          </td>
          <td class="num">{{ $supplier->purchases_count }}</td>
          <td class="num">
            <span class="acct-balance {{ $supplier->balances['payable'] > 0 ? 'acct-balance--warn' : '' }}">
              {{ number_format($supplier->balances['payable'], 2) }}
            </span>
          </td>
          <td>
            <a href="{{ route('accounts.show', $supplier) }}" class="btn secondary small">Manage Ledger &amp; Payments</a>
          </td>
        </tr>
        @empty
        <tr><td colspan="4" class="empty">No suppliers yet. Add one from Yarn Purchases.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ── Add Customer Account ── --}}
<div class="form-section-card" id="add-party">
  <div class="form-section-head">
    <div class="form-section-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
    </div>
    <div>
      <div class="form-section-title">Add Customer Account</div>
      <div class="form-section-sub">Create a customer-only account. Suppliers are added from Yarn Purchases.</div>
    </div>
  </div>
  <form method="POST" action="{{ route('parties.store') }}">
    @csrf
    <input type="hidden" name="return_to" value="accounts">
    <input type="hidden" name="type" value="customer">
    <div class="form-grid">
      <div class="field span-2">
        <label>Customer Name</label>
        <input name="name" required maxlength="150" placeholder="Customer name" value="{{ old('name') }}">
      </div>
      <div class="field">
        <label>Phone</label>
        <input name="phone" maxlength="60">
      </div>
      <div class="field span-2">
        <label>Address <span class="hint-inline">(optional)</span></label>
        <input name="address" maxlength="1000">
      </div>
      <div class="field">
        <label>Opening Receivable (PKR)</label>
        <input name="opening_receivable" type="number" min="0" step="0.01" value="0">
      </div>
    </div>
    <div class="form-submit-bar">
      <button class="btn" type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:7px"><polyline points="20 6 9 17 4 12"/></svg>
        Create Customer Account
      </button>
    </div>
  </form>
</div>

@endsection
