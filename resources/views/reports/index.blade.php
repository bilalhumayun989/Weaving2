@extends('layouts.app')
@section('title','Reports')
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Management Information</div>
    <h1>Reports</h1>
    <p class="tagline">By factory and across the entire business. Production reports are intentionally excluded.</p>
  </div>
  <div class="actions">
    <a class="btn secondary small" href="{{ route('export.transactions', ['kind' => 'bills']) }}">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export Bills
    </a>
    <a class="btn secondary small" href="{{ route('export.transactions', ['kind' => 'purchases']) }}">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export Purchases
    </a>
    <a class="btn secondary small" href="{{ route('export.stock') }}">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export Stock
    </a>
  </div>
</div>

{{-- ── Tab navigation ── --}}
@include('reports._navigation')

{{-- ── Factory Summary ── --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Factory-wise Summary</h2>
      <p class="muted small" style="margin:4px 0 0">Sales totals include provisional and final bills; void bills are excluded.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Factory</th>
          <th class="num">Yarn Purchases (PKR)</th>
          <th class="num">Fabric Sales (PKR)</th>
          <th class="num">Job Work Bills (PKR)</th>
          <th class="num">Available Metres</th>
        </tr>
      </thead>
      <tbody>
        @foreach($factories as $f)
        <tr>
          <td><span style="font-weight:650">{{ $f->name }}</span></td>
          <td class="num">{{ number_format($f->yarn_total, 2) }}</td>
          <td class="num">{{ number_format($f->sales_total, 2) }}</td>
          <td class="num">{{ number_format($f->conversion_total, 2) }}</td>
          <td class="num">{{ number_format($f->available_metres, 2) }}</td>
        </tr>
        @endforeach
        <tr class="report-total-row">
          <td><strong>Combined</strong></td>
          <td class="num"><strong>Rs {{ number_format($factories->sum('yarn_total'), 2) }}</strong></td>
          <td class="num"><strong>Rs {{ number_format($factories->sum('sales_total'), 2) }}</strong></td>
          <td class="num"><strong>Rs {{ number_format($factories->sum('conversion_total'), 2) }}</strong></td>
          <td class="num"><strong>{{ number_format($factories->sum('available_metres'), 2) }}</strong></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

{{-- ── Customer Bag Balances ── --}}
<div class="card">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Customer-Supplied Job Work Bag Balances</h2>
      <p class="muted small" style="margin:4px 0 0">Required vs received bags across active customer-supplied contracts.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Contract</th>
          <th>Party</th>
          <th>Factory</th>
          <th class="num">Required Bags</th>
          <th class="num">Received Bags</th>
          <th class="num">Remaining Bags</th>
        </tr>
      </thead>
      <tbody>
        @forelse($contracts as $c)
        @php $remaining = max(0, (float)$c->required_bags - (float)($c->receipts_sum_bags ?? 0)); @endphp
        <tr>
          <td><a href="{{ route('contracts.show', $c) }}" class="ref-link">{{ $c->reference }}</a></td>
          <td>{{ $c->party->name }}</td>
          <td>{{ $c->factory->name }}</td>
          <td class="num">{{ number_format($c->required_bags, 2) }}</td>
          <td class="num">{{ number_format($c->receipts_sum_bags ?? 0, 2) }}</td>
          <td class="num">
            <span class="{{ $remaining > 0 ? 'report-remaining--warn' : 'report-remaining--ok' }}">
              {{ number_format($remaining, 2) }}
            </span>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="empty">No customer-supplied job-work contracts yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
