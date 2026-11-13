@extends('print.layout')
@section('document')

{{-- ── Purchase meta header ── --}}
<div class="meta cols">
  <div>
    <p><strong>Date:</strong> {{ $purchase->purchased_on }}</p>
    <p><strong>Factory:</strong> {{ ($purchase->factory_scope ?? 'single') === 'all' ? 'All Factories' : $purchase->factory->name }}</p>
    <p><strong>Invoice / Challan:</strong> {{ $purchase->invoice_no ?: '—' }}</p>
    <p><strong>Yarn Type:</strong> {{ ucfirst($purchase->yarn_type ?? 'Unclassified') }}</p>
  </div>
  <div>
    <p><strong>Supplier:</strong> {{ $purchase->party->name }}</p>
    <p><strong>Purchase Ref:</strong> #{{ $purchase->id }}</p>
    <p><strong>Contract:</strong> {{ $purchase->contract?->reference ?? 'General stock' }}</p>
    @if($purchase->payment_terms_note)
    <p><strong>Broker / Terms Note:</strong> {{ $purchase->payment_terms_note }}</p>
    @endif
  </div>
</div>

{{-- ── Reversed banner ── --}}
@if($purchase->voided_at)
<div style="background:#fff0f0;border:2px solid #f1c8c8;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#8e3030;font-weight:700;font-size:14px;text-align:center;letter-spacing:.04em">
  ⚠ REVERSED — DO NOT USE
</div>
@endif

{{-- ── Line items ── --}}
<h2>Purchase Details</h2>
<table>
  <thead>
    <tr>
      <th>Yarn Quality</th>
      <th class="right">Bags</th>
      <th class="right">Rate per Bag (PKR)</th>
      <th class="right">Amount (PKR)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>
        {{ $purchase->yarnQuality?->name ?? $purchase->yarn_description }}{{ $purchase->yarnQuality?->count ? ' · '.$purchase->yarnQuality->count : '' }}
      </td>
      <td class="right">{{ number_format($purchase->bags, 3) }}</td>
      <td class="right">{{ number_format($purchase->rate_per_bag, 4) }}</td>
      <td class="right">{{ number_format($purchase->total, 2) }}</td>
    </tr>
  </tbody>
</table>

{{-- ── Total row ── --}}
<div style="background:#eaf2f0;border:1px solid #c5dcd8;border-radius:7px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <span style="font-size:13px;color:#526870">Grand Total</span>
  <span style="font-size:20px;font-weight:800;color:#1b4444">Rs {{ number_format($purchase->total, 2) }}</span>
</div>

{{-- ── Payment terms ── --}}
<h2>Payment Terms</h2>
<table>
  <tbody>
    <tr>
      <td style="width:40%"><strong>Payment Terms</strong></td>
      <td>{{ $purchase->payment_terms_days !== null ? $purchase->payment_terms_days.' days' : 'Not specified' }}</td>
    </tr>
    <tr>
      <td><strong>Due Date</strong></td>
      <td>{{ $purchase->payment_due_on ?? 'Not specified' }}</td>
    </tr>
    @if($purchase->payment_terms_note)
    <tr>
      <td><strong>Broker / Terms Note</strong></td>
      <td>{{ $purchase->payment_terms_note }}</td>
    </tr>
    @endif
  </tbody>
</table>

@endsection
