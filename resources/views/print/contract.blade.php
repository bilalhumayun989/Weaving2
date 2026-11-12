@extends('print.layout')
@section('document')
<div class="meta cols">
  <div>
    <p><strong>Contract:</strong> {{ $contract->reference }}</p>
    <p><strong>Date:</strong> {{ $contract->contract_date }}</p>
    <p><strong>Type:</strong> {{ $contract->type==='job_work'?'Job Work':'Fabric Rate' }}</p>
    <p><strong>Status:</strong> {{ ucfirst($contract->status) }}</p>
  </div>
  <div>
    <p><strong>Customer / Party:</strong> {{ $contract->party->name }}</p>
    <p><strong>Factory:</strong> {{ $contract->factory->name }}</p>
    <p><strong>Fabric quality:</strong> {{ $contract->quality->name }}</p>
    <p><strong>Yarn supplied by:</strong> {{ $contract->type==='job_work'?($contract->yarn_supplier==='customer'?'Customer / Party':'Factory'): 'Factory' }}</p>
  </div>
</div>

<table>
  <thead><tr><th>Agreed Term</th><th class="right">Value</th></tr></thead>
  <tbody>
    <tr><td>Target quantity</td><td class="right">{{ $contract->target_metres?number_format($contract->target_metres,2).' m':'Not specified' }}</td></tr>
    @if($contract->type==='job_work')
      <tr><td>Conversion rate</td><td class="right">Rs {{ number_format($contract->rate_per_pick,4) }} per pick</td></tr>
    @else
      <tr><td>Fabric rate</td><td class="right">Rs {{ number_format($contract->rate_per_metre,4) }} per metre</td></tr>
    @endif
  </tbody>
</table>

@if($contract->type==='job_work')
@php($parta=$contract->parta_snapshot['results'] ?? [])
<h2>Yarn Requirement and Received Bags</h2>
<table>
  <thead><tr><th>Yarn Type</th><th class="right">Required Bags</th><th class="right">Received Bags</th><th class="right">Remaining Bags</th></tr></thead>
  <tbody>
    <tr><td>Warp</td><td class="right">{{ number_format($parta['warp_bags'] ?? 0,2) }}</td><td class="right">{{ number_format($warpReceived,2) }}</td><td class="right">{{ number_format(max(0,($parta['warp_bags'] ?? 0)-$warpReceived),2) }}</td></tr>
    <tr><td>Weft</td><td class="right">{{ number_format($parta['weft_bags'] ?? 0,2) }}</td><td class="right">{{ number_format($weftReceived,2) }}</td><td class="right">{{ number_format(max(0,($parta['weft_bags'] ?? 0)-$weftReceived),2) }}</td></tr>
    <tr><th>Total</th><th class="right">{{ number_format($parta['total_required_bags'] ?? $contract->required_bags ?? 0,2) }}</th><th class="right">{{ number_format($warpReceived+$weftReceived,2) }}</th><th class="right">{{ number_format(max(0,($contract->required_bags ?? 0)-$warpReceived-$weftReceived),2) }}</th></tr>
  </tbody>
</table>

@if($contract->parta_snapshot)
<p class="muted">Parta: {{ number_format($contract->parta_snapshot['inputs']['quantity_metres'],2) }} m · Reed {{ $contract->parta_snapshot['inputs']['reed'] }} · Picks {{ $contract->parta_snapshot['inputs']['picks'] }} · Width {{ $contract->parta_snapshot['inputs']['width'] }} inches. Bag weight: 100 lbs.</p>
@endif

@if($contract->yarn_supplier==='customer')
<h2>Customer Yarn Receipts</h2>
<table><thead><tr><th>Date Received</th><th>Warp / Weft</th><th>Reference</th><th class="right">Bags</th><th>Notes</th></tr></thead><tbody>
@forelse($receipts as $receipt)<tr><td>{{ $receipt->received_on }}</td><td>{{ $receipt->yarn_type?ucfirst($receipt->yarn_type):'Unclassified' }}</td><td>{{ $receipt->reference ?? '—' }}</td><td class="right">{{ number_format($receipt->bags,2) }}</td><td>{{ $receipt->notes }}</td></tr>@empty<tr><td colspan="5">No customer yarn received yet.</td></tr>@endforelse
</tbody></table>
@else
<h2>Supplier Yarn Deliveries</h2>
<table><thead><tr><th>Date Received</th><th>Supplier</th><th>Warp / Weft</th><th>Description</th><th class="right">Bags</th><th class="right">Rate / Bag</th><th class="right">Total</th></tr></thead><tbody>
@forelse($purchases as $purchase)<tr><td>{{ $purchase->purchased_on }}</td><td>{{ $purchase->party->name }}</td><td>{{ ucfirst($purchase->yarn_type) }}</td><td>{{ $purchase->yarn_description }}</td><td class="right">{{ number_format($purchase->bags,2) }}</td><td class="right">Rs {{ number_format($purchase->rate_per_bag,4) }}</td><td class="right">Rs {{ number_format($purchase->total,2) }}</td></tr>@empty<tr><td colspan="7">No supplier yarn received yet.</td></tr>@endforelse
</tbody></table>
@endif
@endif

@if($contract->parta_details)<h2>Parta / Contract Remarks</h2><p>{{ $contract->parta_details }}</p>@endif
@if($contract->notes)<h2>Other Remarks</h2><p>{{ $contract->notes }}</p>@endif
@endsection
