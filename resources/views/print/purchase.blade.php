@extends('print.layout')
@section('document')
<div class="meta cols"><div><p><strong>Date:</strong> {{ $purchase->purchased_on }}</p><p><strong>Factory:</strong> {{ $purchase->factory->name }}</p><p><strong>Invoice / Challan:</strong> {{ $purchase->invoice_no ?: '—' }}</p></div><div><p><strong>Supplier:</strong> {{ $purchase->party->name }}</p><p><strong>Reference:</strong> Purchase #{{ $purchase->id }}</p></div></div>
@if($purchase->voided_at)<h2 style="color:#bb3333">REVERSED — DO NOT USE</h2>@endif
<table><thead><tr><th>Yarn description</th><th class="right">Bags</th><th class="right">Rate per bag</th><th class="right">Amount</th></tr></thead><tbody><tr><td>{{ $purchase->yarn_description }}</td><td class="right">{{ number_format($purchase->bags,3) }}</td><td class="right">{{ number_format($purchase->rate_per_bag,4) }}</td><td class="right">{{ number_format($purchase->total,2) }}</td></tr></tbody></table>
<p><strong>Cones per bag:</strong> {{ $purchase->cones_per_bag ?? "Not specified" }} <small>(100 lbs per bag irrespective of cone count)</small></p>
<div class="total">Total: Rs {{ number_format($purchase->total,2) }}</div>
@endsection
