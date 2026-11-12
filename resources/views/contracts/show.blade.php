@extends('layouts.app')
@section('title','Contract')
@section('content')
<div class="page-heading"><div><div class="kicker">Contract Details</div><h1>{{ $contract->reference }}</h1><p class="tagline">{{ $contract->party->name }} · {{ $contract->factory->name }}</p></div><a class="btn secondary" href="{{ route('contracts.index') }}">All Contracts</a></div>
<div class="grid grid-3"><div class="card metric"><div class="label">Contract Type</div><div class="value" style="font-size:21px">{{ $contract->type==='job_work'?'Job Work':'Fabric Rate' }}</div><div class="detail">{{ $contract->quality->name }}</div></div><div class="card metric"><div class="label">Agreed Rate</div><div class="value">Rs {{ number_format($contract->type==='job_work'?$contract->rate_per_pick:$contract->rate_per_metre,4) }}</div><div class="detail">{{ $contract->type==='job_work'?'Per pick':'Per metre' }}</div></div><div class="card metric"><div class="label">Target</div><div class="value">{{ $contract->target_metres?number_format($contract->target_metres,2).' m':'—' }}</div></div></div>
@if($contract->type==='job_work')
<div class="grid grid-3"><div class="card metric"><div class="label">Required Bags</div><div class="value">{{ number_format($contract->required_bags,3) }}</div></div><div class="card metric"><div class="label">Received Bags</div><div class="value">{{ number_format($received,3) }}</div></div><div class="card metric"><div class="label">Remaining Bags</div><div class="value">{{ number_format(max(0,(float)$contract->required_bags-$received),3) }}</div>@if($received>(float)$contract->required_bags)<div class="detail">Over supplied: {{ number_format($received-$contract->required_bags,3) }}</div>@endif</div></div>
@if($contract->parta_snapshot)
@php($p=$contract->parta_snapshot)
<div class="card"><h2>Approved Fabric Costing / Parta</h2>
<div class="note">Calculation saved when this contract was created. The fixed bag weight is <strong>100 lbs</strong> regardless of cone count. The costing estimate below does not post to supplier accounts or COGS.</div>
<div class="table-wrap" style="margin-top:14px"><table><thead><tr><th>Detail</th><th class="num">Warp</th><th class="num">Weft</th></tr></thead><tbody>
<tr><td>Yarn Count</td><td class="num">{{ number_format($p['inputs']['warp_count'],3) }}</td><td class="num">{{ number_format($p['inputs']['weft_count'],3) }}</td></tr>
<tr><td>Weight / Metre (lbs)</td><td class="num">{{ number_format($p['results']['warp_lbs_per_metre'],4) }}</td><td class="num">{{ number_format($p['results']['weft_lbs_per_metre'],4) }}</td></tr>
<tr><td>Required Bags</td><td class="num"><strong>{{ number_format($p['results']['warp_bags'],2) }}</strong></td><td class="num"><strong>{{ number_format($p['results']['weft_bags'],2) }}</strong></td></tr>
@if($p['results']['total_yarn_cost_per_metre']!==null)<tr><td>Yarn Cost / Metre (Rs)</td><td class="num">{{ number_format($p['results']['warp_yarn_cost_per_metre'],4) }}</td><td class="num">{{ number_format($p['results']['weft_yarn_cost_per_metre'],4) }}</td></tr>@endif
</tbody></table></div>
<p class="inline-note">Quantity: {{ number_format($p['inputs']['quantity_metres'],2) }} m · Reed: {{ $p['inputs']['reed'] }} · Picks: {{ $p['inputs']['picks'] }} · Width: {{ $p['inputs']['width'] }} inches</p>
<div class="grid grid-3" style="margin-top:14px"><div><div class="muted small">Total required bags</div><strong>{{ number_format($p['results']['total_required_bags'],2) }}</strong></div><div><div class="muted small">Conversion / metre</div><strong>Rs {{ number_format($p['results']['conversion_per_metre'],4) }}</strong></div><div><div class="muted small">Estimated fabric cost / metre</div><strong>{{ $p['results']['estimated_fabric_rate_per_metre']!==null?'Rs '.number_format($p['results']['estimated_fabric_rate_per_metre'],4):'— (yarn rates not supplied)' }}</strong></div></div>
</div>
@endif
@if($contract->parta_details)<div class="card"><h2>Parta / Contract Remarks</h2><pre style="white-space:pre-wrap;font:inherit">{{ $contract->parta_details }}</pre></div>@endif
@if($contract->status==='active')<div class="card"><h2>Receive Party-Supplied Yarn Bags</h2><form action="{{ route('contracts.bags.store',$contract) }}" method="POST">@csrf<div class="form-grid"><div class="field"><label>Date Received</label><input type="date" name="received_on" value="{{ date('Y-m-d') }}" required></div><div class="field"><label>Bags Received</label><input type="number" step="0.001" min="0.001" name="bags" required></div><div class="field"><label>Cones per Bag (optional)</label><input name="cones_per_bag" type="number" min="1" step="1" placeholder="e.g. 24 or 40"><div class="hint">Bag remains 100 lbs regardless of cones.</div></div><div class="field"><label>Delivery Reference</label><input name="reference"></div><div class="field"><label>Notes</label><input name="notes"></div></div><div class="input-actions"><button class="btn">Record Bags Received</button></div></form></div>
@endif
<div class="card"><h2>Bag Receipt History</h2><table><thead><tr><th>Date</th><th>Reference</th><th class="num">Bags</th><th class="num">Cones / Bag</th></tr></thead><tbody>@forelse($receipts as $r)<tr><td>{{ $r->received_on }}</td><td>{{ $r->reference ?? '—' }}</td><td class="num">{{ number_format($r->bags,3) }}</td><td class="num">{{ $r->cones_per_bag ?? "—" }}</td></tr>@empty<tr><td colspan="4" class="empty">No receipts yet.</td></tr>@endforelse</tbody></table></div>
@else<div class="card"><div class="note">Factory purchases yarn in the Yarn section. Fabric is recorded directly through checking; no production entry required.</div><br><a class="btn secondary" href="{{ route('yarn.index') }}">Open Yarn Purchases</a></div>@endif
@if($contract->status==='active' && auth()->user()->role==='admin')
<div class="card"><h2>Close Contract</h2><p class="muted small">Closure is only allowed when no unsent fabric or unresolved Kachi/Pakki remains. This does not create any unagreed financial settlement.</p>
<form method="POST" action="{{ route('contracts.close',$contract) }}" onsubmit="return confirm('Close contract permanently for new activity?')">@csrf
<div class="form-grid"><div class="field"><label>Closure Date</label><input type="date" name="closed_on" value="{{ date('Y-m-d') }}" required></div><div class="field"><label>Reason</label><input name="close_reason" required minlength="8" placeholder="Contract completed / settled"></div></div>
<div class="input-actions"><button class="btn secondary">Close Contract</button></div></form></div>
@endif
@endsection
