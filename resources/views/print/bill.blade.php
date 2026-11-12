@extends('print.layout')
@section('document')
<div class="meta cols"><div><p><strong>Invoice:</strong> {{ $bill->reference }}</p><p><strong>Invoice date:</strong> {{ $bill->billed_on }}</p><p><strong>Factory:</strong> {{ $bill->factory->name }}</p></div><div><p><strong>Bill to:</strong> {{ $bill->party->name }}</p><p><strong>Contract:</strong> {{ $bill->contract?->reference ?? 'Stock Sale' }}</p><p><strong>Kachi:</strong> {{ $bill->kachi->reference }}</p></div></div>
@php($factoryYarnJobWork=$bill->type==='conversion' && $bill->contract?->yarn_supplier==='factory')
@php($jobWorkRatePerMetre=$factoryYarnJobWork?$bill->rate:(float)$bill->picks*(float)$bill->rate)
<table><thead><tr><th>Description</th><th class="right">Accepted metres</th><th class="right">{{ $bill->type==='conversion'?'Rate / m':'Rate / m' }}</th><th class="right">Amount (PKR)</th></tr></thead><tbody>
<tr><td>{{ $bill->type==='conversion'?($factoryYarnJobWork?'Job Work — Factory-Supplied Yarn':'Job Work — Customer-Supplied Yarn'):'Factory-Owned Fabric Sale' }} @if($bill->contract) · {{ $bill->contract->quality?->name }} @endif</td><td class="right">{{ number_format($bill->accepted_metres,2) }}</td><td class="right">{{ number_format($bill->type==='conversion'?$jobWorkRatePerMetre:$bill->rate,4) }}</td><td class="right">{{ number_format($bill->total,2) }}</td></tr>
</tbody></table>
<div class="total">Total Payable: Rs {{ number_format($bill->total,2) }}</div>
<p class="muted">Calculation: {{ number_format($bill->accepted_metres,2) }} accepted metres × Rs {{ number_format($bill->type==='conversion'?$jobWorkRatePerMetre:$bill->rate,4) }} per metre. @if($bill->type==='conversion'&&$factoryYarnJobWork)This rate includes the contract yarn estimate and conversion charge.@elseif($bill->type==='conversion')This is the agreed conversion charge; yarn was supplied by the customer.@endif</p>
<p class="muted">Only customer-accepted Pakki metres have been billed. Taxes and COGS are not included in this scope.</p>
@endsection
