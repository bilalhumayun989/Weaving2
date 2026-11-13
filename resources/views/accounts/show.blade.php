@extends('layouts.app')
@section('title', $party->name . ' · Ledger')
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">{{ $party->type === 'supplier' ? 'Supplier' : 'Customer' }} Account</div>
    <h1>{{ $party->name }}</h1>
    <p class="tagline">
      {{ $party->type === 'both' ? 'Combined customer and supplier activity' : ($party->type === 'supplier' ? 'Supplier purchases and payment activity' : 'Customer sales and payment activity') }}
      &middot; Factory shown per applicable transaction
    </p>
  </div>
  <div class="actions">
    <a class="btn secondary" href="{{ route('print.ledger', $party) }}" target="_blank">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print Statement
    </a>
    <a class="btn secondary" href="{{ route('export.ledger', $party) }}">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </a>
    <a class="btn secondary" href="{{ route('accounts.index') }}">All Accounts</a>
  </div>
</div>

{{-- ── Balance cards ── --}}
<div class="grid" style="margin-bottom:24px">
  @if($party->type !== 'supplier')
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--green">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Receivable Balance</div>
      <div class="dmc-value">Rs&nbsp;{{ number_format($receivable['closing'], 2) }}</div>
      <div class="dmc-sub">Customer amount outstanding</div>
    </div>
  </div>
  @endif
  @if($party->type !== 'customer')
  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--amber">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Payable Balance</div>
      <div class="dmc-value">Rs&nbsp;{{ number_format($payable['closing'], 2) }}</div>
      <div class="dmc-sub">Supplier amount outstanding</div>
    </div>
  </div>
  @endif
</div>

{{-- ── Record Payment ── --}}
<div class="form-section-card" style="margin-bottom:24px">
  <div class="form-section-head">
    <div class="form-section-icon dmc-icon--green" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
    </div>
    <div>
      <div class="form-section-title">{{ $party->type === 'supplier' ? 'Record Supplier Payment' : 'Record Customer Payment' }}</div>
      <div class="form-section-sub">
        @if($party->type === 'supplier') Enter the amount paid to this supplier. It reduces the payable balance.
        @elseif($party->type === 'customer') Enter the amount received from this customer. It reduces the receivable balance.
        @else Choose whether money was received from or paid to this customer/supplier.
        @endif
      </div>
    </div>
  </div>
  <form method="POST" action="{{ route('accounts.payments.store') }}">
    @csrf
    <input type="hidden" name="party_id" value="{{ $party->id }}">
    <div class="form-grid">

      @if($party->type === 'both')
      <div class="field">
        <label>Payment Type</label>
        <select name="kind">
          <option value="received" @selected(!request('yarn_purchase_id'))>Received from Customer</option>
          <option value="paid" @selected(request('yarn_purchase_id'))>Paid to Supplier</option>
        </select>
      </div>
      @else
      <input type="hidden" name="kind" value="{{ $party->type === 'supplier' ? 'paid' : 'received' }}">
      @endif

      @if($party->type !== 'customer')
      <div class="field span-2" id="yarnPurchasePaymentField">
        <label>Apply to Yarn Purchase <span class="hint-inline">(optional)</span></label>
        <select name="yarn_purchase_id">
          <option value="">General supplier payment</option>
          @foreach($openYarnPurchases as $purchase)
            <option value="{{ $purchase->id }}" @selected(request('yarn_purchase_id') == $purchase->id)>
              {{ $purchase->invoice_no ?: 'Purchase #'.$purchase->id }} &middot; {{ $purchase->factory->name }} &middot; due {{ $purchase->payment_due_on ?: 'date not set' }} &middot; Rs {{ number_format($purchase->outstanding_amount, 2) }} remaining
            </option>
          @endforeach
        </select>
        <div class="hint">Link the payment to a purchase to update its due amount and remove its dashboard reminder.</div>
      </div>
      @endif

      <div class="field">
        <label>Payment Date</label>
        <input name="paid_on" type="date" value="{{ date('Y-m-d') }}" required>
      </div>
      <div class="field">
        <label>Amount (PKR)</label>
        <input name="amount" type="number" min="0.01" step="0.01" required>
      </div>
      <div class="field">
        <label>Factory <span class="hint-inline">(optional)</span></label>
        <select name="factory_id">
          <option value="">Combined Business / Not Assigned</option>
          @foreach($factories as $f)
            <option value="{{ $f->id }}">{{ $f->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>Payment Reference</label>
        <input name="reference" placeholder="Cash, transfer ref, receipt no.">
      </div>
      <div class="field">
        <label>Notes</label>
        <input name="notes">
      </div>
    </div>
    <div class="form-submit-bar">
      <button class="btn" type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:7px"><polyline points="20 6 9 17 4 12"/></svg>
        {{ $party->type === 'supplier' ? 'Record Supplier Payment' : ($party->type === 'customer' ? 'Record Customer Payment' : 'Save Payment') }}
      </button>
    </div>
  </form>
</div>

{{-- ── Recent Payments ── --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Recent Payments &amp; Reversals</h2>
      <p class="muted small" style="margin:4px 0 0">All recorded payments and any admin reversals.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Type</th>
          <th class="num">Amount (PKR)</th>
          <th>Reference</th>
          <th>Status / Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $pay)
        <tr>
          <td>{{ $pay->paid_on }}</td>
          <td>
            <span class="pill {{ $pay->kind === 'received' ? '' : 'dark' }}">
              {{ $pay->kind === 'received' ? 'Received' : 'Paid' }}
            </span>
          </td>
          <td class="num"><strong>Rs {{ number_format($pay->amount, 2) }}</strong></td>
          <td>{{ $pay->reference ?: '—' }}</td>
          <td>
            @if($pay->voided_at)
              <span class="pill warn">Reversed {{ $pay->voided_at }}</span>
              <div class="small muted" style="margin-top:4px">{{ $pay->void_reason }}</div>
            @elseif(auth()->user()->role === 'admin')
              <form method="POST" action="{{ route('corrections.payment', $pay) }}" onsubmit="return confirm('Reverse this payment? The correction stays in the running ledger.')">
                @csrf
                <div class="acct-reverse-form">
                  <input name="reason" required minlength="8" placeholder="Reason (min. 8 chars)">
                  <button class="btn secondary small" type="submit">Reverse</button>
                </div>
              </form>
            @else
              <span class="pill">Posted</span>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="5" class="empty">No payments recorded yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ── Sales Bills ── --}}
@if($party->type !== 'supplier')
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Sales Bills</h2>
      <p class="muted small" style="margin:4px 0 0">All fabric sale and job work bills for this customer.</p>
    </div>
    <a class="btn secondary small" href="{{ route('accounts.bills', ['party_id' => $party->id]) }}">View All Bills</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Bill Ref</th>
          <th>Factory</th>
          <th>Contract</th>
          <th class="num">Total (PKR)</th>
        </tr>
      </thead>
      <tbody>
        @forelse($bills as $bill)
        <tr>
          <td>{{ $bill->billed_on }}</td>
          <td>
            <a href="{{ route('print.bill', $bill) }}" target="_blank" class="ref-link">{{ $bill->reference }}</a>
            <div class="small muted">{{ ucfirst(str_replace('_', ' ', $bill->type)) }} &middot; {{ number_format($bill->accepted_metres, 2) }} m &middot; <span class="status-pill status-{{ $bill->billing_status }}">{{ ucfirst($bill->billing_status) }}</span></div>
          </td>
          <td>{{ $bill->factory->name }}</td>
          <td>{{ $bill->contract?->reference ?? '—' }}</td>
          <td class="num">{{ number_format($bill->total, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="empty">No sales bills for this customer yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endif

{{-- ── Yarn Purchases ── --}}
@if($party->type !== 'customer')
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Yarn Purchases</h2>
      <p class="muted small" style="margin:4px 0 0">All recorded yarn purchases from this supplier.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Purchase</th>
          <th>Factory</th>
          <th class="num">Bags</th>
          <th class="num">Total (PKR)</th>
        </tr>
      </thead>
      <tbody>
        @forelse($purchases as $purchase)
        <tr>
          <td>{{ $purchase->purchased_on }}</td>
          <td>
            <a href="{{ route('print.purchase', $purchase) }}" target="_blank" class="ref-link">
              {{ $purchase->invoice_no ?: 'Purchase #'.$purchase->id }}
            </a>
            <div class="small muted">
              {{ $purchase->yarn_description }}
              @if($purchase->voided_at)<span class="pill warn" style="margin-left:4px">Reversed</span>@endif
            </div>
          </td>
          <td>{{ $purchase->factory->name }}</td>
          <td class="num">{{ number_format($purchase->bags, 3) }}</td>
          <td class="num">{{ number_format($purchase->total, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="empty">No yarn purchases for this party yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endif

{{-- ── Running Ledger(s) ── --}}
@foreach(($party->type === 'customer' ? ['receivable' => 'Customer Receivable Ledger'] : ($party->type === 'supplier' ? ['payable' => 'Supplier Payable Ledger'] : ['receivable' => 'Customer Receivable Ledger', 'payable' => 'Supplier Payable Ledger'])) as $key => $heading)
@php $data = $key === 'receivable' ? $receivable : $payable; @endphp
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>{{ $heading }}</h2>
      <p class="muted small" style="margin:4px 0 0">
        {{ $key === 'receivable' ? 'Invoices increase debit; payments received increase credit.' : 'Supplier purchases increase credit; supplier payments increase debit.' }}
      </p>
    </div>
    <div class="ledger-closing-badge">
      <div class="lcb-label">Closing Balance</div>
      <div class="lcb-value">Rs {{ number_format($data['closing'], 2) }}</div>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Transaction</th>
          <th>Factory</th>
          <th class="num">Debit</th>
          <th class="num">Credit</th>
          <th class="num">Running Balance</th>
        </tr>
      </thead>
      <tbody>
        <tr class="ledger-opening-row">
          <td class="muted small">—</td>
          <td><strong>Opening Balance</strong></td>
          <td class="muted small">—</td>
          <td class="num muted">—</td>
          <td class="num muted">—</td>
          <td class="num"><strong>{{ number_format($data['opening'], 2) }}</strong></td>
        </tr>
        @foreach($data['rows'] as $row)
        <tr>
          <td>{{ $row['entry']->entry_on }}</td>
          <td>{{ $row['entry']->description }}</td>
          <td>{{ $row['entry']->factory?->name ?? 'Combined' }}</td>
          <td class="num">{{ $row['entry']->debit > 0 ? number_format($row['entry']->debit, 2) : '—' }}</td>
          <td class="num">{{ $row['entry']->credit > 0 ? number_format($row['entry']->credit, 2) : '—' }}</td>
          <td class="num"><strong>{{ number_format($row['balance'], 2) }}</strong></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endforeach

@if($party->type === 'both')
@push('scripts')
<script>
(()=>{
  const kind  = document.querySelector('select[name="kind"]');
  const field = document.getElementById('yarnPurchasePaymentField');
  if (!kind || !field) return;
  const sel = field.querySelector('select');
  function update() {
    const visible = kind.value === 'paid';
    field.style.display = visible ? 'flex' : 'none';
    if (!visible) sel.value = '';
  }
  kind.addEventListener('change', update);
  update();
})();
</script>
@endpush
@endif

@endsection
