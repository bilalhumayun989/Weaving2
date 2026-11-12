@extends('print.layout')
@section('document')
<div class="meta cols"><div><p><strong>Kachi:</strong> {{ $kachi->reference }}</p><p><strong>Factory:</strong> {{ $kachi->factory->name }}</p><p><strong>Dispatch date:</strong> {{ $kachi->dispatched_on }}</p></div><div><p><strong>Party:</strong> {{ $kachi->party->name }}</p><p><strong>Contract:</strong> {{ $kachi->contract?->reference ?? 'Own stock sale' }}</p><p><strong>Type:</strong> {{ $kachi->kind==='job_work'?'Job Work':'Fabric Sale' }}</p></div></div>
@if($kachi->voided_at)<h2 style="color:#bb3333">CANCELLED — DO NOT USE</h2>@endif
<table><thead><tr><th>#</th><th>Thaan No.</th><th class="right">Measured length (m)</th></tr></thead><tbody>
@foreach($kachi->items as $i=>$item)<tr><td>{{ $i+1 }}</td><td>{{ $item->thaan->thaan_no }}</td><td class="right">{{ number_format($item->metres,2) }}</td></tr>@endforeach
</tbody></table><div class="total">{{ $kachi->items->count() }} Thaans · {{ number_format($kachi->items->sum('metres'),2) }} metres dispatched</div>
<p class="muted">Dispatch document. Accepted metres are determined by the related Pakki Parchi.</p>
@endsection
