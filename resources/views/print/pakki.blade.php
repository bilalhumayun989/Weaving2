@extends('print.layout')
@section('document')
<div class="meta cols"><div><p><strong>Kachi:</strong> {{ $kachi->reference }}</p><p><strong>Factory:</strong> {{ $kachi->factory->name }}</p><p><strong>Pakki date:</strong> {{ $kachi->pakki->accepted_on }}</p></div><div><p><strong>Party:</strong> {{ $kachi->party->name }}</p><p><strong>Contract:</strong> {{ $kachi->contract?->reference ?? 'Own stock sale' }}</p><p><strong>Bill:</strong> {{ $kachi->bill?->reference ?? '—' }}</p></div></div>
<table><tbody><tr><th>Dispatched metres</th><td class="right">{{ number_format($kachi->items->sum('metres'),2) }}</td></tr><tr><th>Accepted metres</th><td class="right">{{ number_format($kachi->pakki->accepted_metres,2) }}</td></tr><tr><th>Rejected metres</th><td class="right">{{ number_format($kachi->pakki->rejected_metres,2) }}</td></tr><tr><th>Accepted Thaan count</th><td class="right">{{ $kachi->pakki->accepted_thaan_count }}</td></tr><tr><th>Rejected Thaan count</th><td class="right">{{ $kachi->items->count()-$kachi->pakki->accepted_thaan_count }}</td></tr></tbody></table>
<div class="total">Billable / accepted metres: {{ number_format($kachi->pakki->accepted_metres,2) }}</div>
<p class="muted">Pakki records totals, not individual rejected Thaan IDs. Physical returns are reconciled using original markings.</p>
@endsection
