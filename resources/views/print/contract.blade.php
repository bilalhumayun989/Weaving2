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
    <p><strong>Factory:</strong> {{ ($contract->factory_scope ?? 'single')==='all'?'All Factories':$contract->factory->name }}</p>
    <p><strong>Fabric quality:</strong> {{ $contract->quality->name }}</p>
    <p><strong>Yarn supplied by:</strong> {{ $contract->type==='job_work'?($contract->yarn_supplier==='customer'?'Customer / Party':'Factory'): 'Factory' }}</p>
  </div>
</div>

@php($printDetails=$contract->print_details ?? [])
@php($businessRows=[
  'Sales Tax Registration No.'=>$printDetails['sales_tax_registration_no']??null,
  'NTN'=>$printDetails['ntn']??null,
  'Broker'=>$printDetails['broker']??null,
  'Grey Demand No.'=>$printDetails['grey_demand_no']??null,
  'Weave Quality'=>$printDetails['weave_quality']??null,
  'Invoice Quality'=>$printDetails['invoice_quality']??null,
  'Specification'=>$printDetails['specification']??null,
  'Warp Component'=>$printDetails['warp_component']??null,
  'Weft Component'=>$printDetails['weft_component']??null,
  'HS Code / Description'=>$printDetails['hs_code_description']??null,
  'Last Date of Delivery'=>$printDetails['last_delivery_date']??null,
])
@if(collect($businessRows)->contains(fn($value)=>$value!==null && $value!==''))
<h2>Contract and Fabric Details</h2>
<table><tbody>@foreach($businessRows as $label=>$value)@if($value!==null && $value!=='')<tr><td><strong>{{ $label }}</strong></td><td>{{ $value }}</td></tr>@endif @endforeach</tbody></table>
@endif

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

@php($fabricRows=[
  'Broker Commission (%)'=>$printDetails['broker_commission_percent']??null,
  'Planned Looms'=>$printDetails['planned_looms']??null,
  'Weight per Metre (kg)'=>$printDetails['weight_per_metre_kg']??null,
  'Selvedge'=>$printDetails['selvedge']??null,
  'Weave'=>$printDetails['weave']??null,
  'Count Margin'=>$printDetails['count_margin']??null,
  'Loom Type'=>$printDetails['loom_type']??null,
  'Stripe Size'=>$printDetails['stripe_size']??null,
  'Process'=>$printDetails['process']??null,
  'Payment Terms'=>$printDetails['payment_terms']??null,
  'Packing'=>$printDetails['packing']??null,
  'Special Remarks'=>$printDetails['special_remarks']??null,
])
@if(collect($fabricRows)->contains(fn($value)=>$value!==null && $value!==''))
<h2>Fabric Specification and Commercial Terms</h2>
<table><tbody>@foreach($fabricRows as $label=>$value)@if($value!==null && $value!=='')<tr><td><strong>{{ $label }}</strong></td><td>{{ $value }}</td></tr>@endif @endforeach</tbody></table>
@endif
@if(!empty($printDetails['delivery_schedule']))
<h2>Delivery Schedule</h2>
<table><thead><tr><th>Date</th><th class="right">Quantity</th><th>UOM</th><th>Location</th></tr></thead><tbody>
@foreach($printDetails['delivery_schedule'] as $delivery)<tr><td>{{ $delivery['date'] }}</td><td class="right">{{ number_format((float)$delivery['quantity'],2) }}</td><td>{{ $delivery['uom']??'M' }}</td><td>{{ $delivery['location']??'' }}</td></tr>@endforeach
</tbody></table>
@endif
@if(!empty($printDetails['terms_conditions']))<h2>Terms &amp; Conditions</h2><p style="white-space:pre-line">{{ $printDetails['terms_conditions'] }}</p>@endif

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
<h2>Warp / Weft Costing Details</h2>
<table><thead><tr><th>Detail</th><th class="right">Warp</th><th class="right">Weft</th><th class="right">Total</th></tr></thead><tbody>
<tr><td>Weight / Metre (lbs)</td><td class="right">{{ number_format($contract->parta_snapshot['results']['warp_lbs_per_metre'],4) }}</td><td class="right">{{ number_format($contract->parta_snapshot['results']['weft_lbs_per_metre'],4) }}</td><td class="right">{{ number_format($contract->parta_snapshot['results']['total_lbs_per_metre'],4) }}</td></tr>
@if(($contract->parta_snapshot['inputs']['warp_yarn_rate']??null)!==null && ($contract->parta_snapshot['inputs']['weft_yarn_rate']??null)!==null)<tr><td>Yarn Rate (PKR / lb)</td><td class="right">{{ number_format($contract->parta_snapshot['inputs']['warp_yarn_rate'],2) }}</td><td class="right">{{ number_format($contract->parta_snapshot['inputs']['weft_yarn_rate'],2) }}</td><td class="right">—</td></tr>@endif
<tr><td>Conversion / Metre (PKR)</td><td class="right">—</td><td class="right">—</td><td class="right">{{ number_format($contract->parta_snapshot['results']['conversion_per_metre'],4) }}</td></tr>
@if(($contract->parta_snapshot['results']['estimated_fabric_rate_per_metre']??null)!==null)<tr><td>Estimated Fabric Cost / Metre (PKR)</td><td class="right">—</td><td class="right">—</td><td class="right">{{ number_format($contract->parta_snapshot['results']['estimated_fabric_rate_per_metre'],4) }}</td></tr>@endif
</tbody></table>
@endif

@if($contract->yarn_supplier==='customer')
<h2>Customer Yarn Receipts</h2>
<table><thead><tr><th>Date Received</th><th>Warp / Weft</th><th>Delivery Reference</th><th class="right">Bags</th><th>Cones / Bag</th><th>Notes</th></tr></thead><tbody>
@forelse($receipts as $receipt)<tr><td>{{ $receipt->received_on }}</td><td>{{ $receipt->yarn_type?ucfirst($receipt->yarn_type):'Unclassified' }}</td><td>{{ $receipt->reference ?? '-' }}</td><td class="right">{{ number_format($receipt->bags,2) }}</td><td>{{ $receipt->cones_per_bag ?? '-' }}</td><td>{{ $receipt->notes }}</td></tr>@empty<tr><td colspan="6">No customer yarn received yet.</td></tr>@endforelse
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
