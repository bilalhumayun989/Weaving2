@extends('print.layout')
@section('document')
<div class="meta cols"><div><p><strong>Invoice:</strong> {{ $bill->reference }}</p><p><strong>Invoice date:</strong> {{ $bill->billed_on }}</p><p><strong>Factory:</strong> {{ $bill->factory->name }}</p></div><div><p><strong>Bill to:</strong> {{ $bill->party->name }}</p><p><strong>Contract:</strong> {{ $bill->contract?->reference ?? 'Stock Sale' }}</p><p><strong>Kachi:</strong> {{ $bill->kachi->reference }}</p></div></div>
<table><thead><tr><th>Description</th><th class="right">Accepted metres</th><th class="right">{{ $bill->type==='conversion'?'Picks':'Rate / m' }}</th><th class="right">{{ $bill->type==='conversion'?'Rate / pick':'Amount (PKR)' }}</th></tr></thead><tbody>
<tr><td>{{ $bill->type==='conversion'?'Job Work Conversion':'Factory-Owned Fabric Sale' }} @if($bill->contract) · {{ $bill->contract->quality?->name }} @endif</td><td class="right">{{ number_format($bill->accepted_metres,2) }}</td><td class="right">{{ $bill->type==='conversion'?number_format($bill->picks,2):number_format($bill->rate,4) }}</td><td class="right">{{ $bill->type==='conversion'?number_format($bill->rate,4):number_format($bill->total,2) }}</td></tr>
</tbody></table>
<div class="total">Total Payable: Rs {{ number_format($bill->total,2) }}</div>
@if($bill->type==='conversion')<p class="muted">Calculation: {{ number_format($bill->accepted_metres,2) }} accepted metres × {{ number_format($bill->picks,2) }} picks × Rs {{ number_format($bill->rate,4) }} per pick.</p>@else<p class="muted">Calculation: {{ number_format($bill->accepted_metres,2) }} accepted metres × Rs {{ number_format($bill->rate,4) }} per metre.</p>@endif
<p class="muted">Only customer-accepted Pakki metres have been billed. Taxes and COGS are not included in this scope.</p>
@endsection
