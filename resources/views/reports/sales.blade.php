@extends('layouts.app')
@section('title','Sales Report')
@section('content')
<div class="page-heading"><div><div class="kicker">Management Information</div><h1>Sales Report</h1><p class="tagline">Every Kachi dispatch creates a provisional sale and customer receivable. Pakki finalizes that same entry; cancelled Kachis are reversed.</p></div></div>
@include('reports._navigation')
<div class="actions" style="margin-bottom:14px"><a class="btn secondary" href="{{ route('export.sales',request()->query()) }}">Export Excel Sheet (CSV)</a></div>
<div class="grid grid-3">
<div class="card metric"><div class="label">Provisional Receivables</div><div class="value">Rs {{ number_format((float)$summary->provisional_total,2) }}</div><div class="detail">Awaiting Pakki acceptance</div></div>
<div class="card metric"><div class="label">Final Sales</div><div class="value">Rs {{ number_format((float)$summary->final_total,2) }}</div><div class="detail">Pakki accepted amount</div></div>
<div class="card metric"><div class="label">Active Sales Total</div><div class="value">Rs {{ number_format((float)$summary->receivable_sales_total,2) }}</div><div class="detail">{{ number_format((float)$summary->receivable_metres,2) }} metres · voids excluded; payments appear in customer account ledgers</div></div>
</div>
<div class="card"><h2>Filter Sales</h2><form method="GET" action="{{ route('reports.sales') }}" class="form-grid">
<div class="field"><label>From</label><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div><div class="field"><label>To</label><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
<div class="field"><label>Factory</label><select name="factory_id"><option value="">All factories</option>@foreach($factories as $f)<option value="{{ $f->id }}" @selected(($filters['factory_id']??'')==$f->id)>{{ $f->name }}</option>@endforeach</select></div>
<div class="field"><label>Customer</label><select name="party_id"><option value="">All customers</option>@foreach($parties as $p)<option value="{{ $p->id }}" @selected(($filters['party_id']??'')==$p->id)>{{ $p->name }}</option>@endforeach</select></div>
<div class="field"><label>Contract</label><select name="contract_id"><option value="">All contracts</option>@foreach($contracts as $c)<option value="{{ $c->id }}" @selected(($filters['contract_id']??'')==$c->id)>{{ $c->reference }} · {{ $c->party->name }}</option>@endforeach</select></div>
<div class="field"><label>Sale Type</label><select name="type"><option value="">All types</option><option value="conversion" @selected(($filters['type']??'')==='conversion')>Job Work</option><option value="fabric_sale" @selected(($filters['type']??'')==='fabric_sale')>Fabric Sale</option></select></div>
<div class="field"><label>Billing Status</label><select name="status"><option value="">All statuses</option><option value="provisional" @selected(($filters['status']??'')==='provisional')>Provisional</option><option value="final" @selected(($filters['status']??'')==='final')>Final</option><option value="void" @selected(($filters['status']??'')==='void')>Void</option></select></div>
<div class="field"><label>&nbsp;</label><button class="btn">Apply Filters</button></div><div class="field"><label>&nbsp;</label><a class="btn secondary" href="{{ route('reports.sales') }}">Clear</a></div>
</form></div>
<div class="card"><h2>Sale Sheet</h2><div class="table-wrap"><table><thead><tr><th>Date</th><th>Bill / Kachi</th><th>Status</th><th>Customer</th><th>Factory</th><th>Contract</th><th>Type</th><th class="num">Metres</th><th class="num">Rate</th><th class="num">Amount (PKR)</th></tr></thead><tbody>
@forelse($sales as $sale)<tr><td>{{ $sale->billed_on }}</td><td><a href="{{ route('print.bill',$sale) }}" target="_blank">{{ $sale->reference }}</a><div class="small muted">{{ $sale->kachi?->reference }}</div></td><td><span class="pill {{ $sale->billing_status==='provisional'?'warn':'' }}">{{ ucfirst($sale->billing_status) }}</span></td><td>{{ $sale->party->name }}</td><td>{{ $sale->factory->name }}</td><td>{{ $sale->contract?->reference ?? 'Stock sale' }}</td><td>{{ $sale->type==='conversion'?'Job Work':'Fabric Sale' }}</td><td class="num">{{ number_format($sale->accepted_metres,2) }}</td><td class="num">Rs {{ number_format($sale->type==='conversion'&&$sale->contract?->yarn_supplier!=='factory'?(float)$sale->picks*(float)$sale->rate:(float)$sale->rate,4) }} / m</td><td class="num">{{ number_format($sale->billing_status==='void'?0:$sale->total,2) }}</td></tr>@empty<tr><td colspan="10" class="empty">No sales match these filters.</td></tr>@endforelse
</tbody></table></div>{{ $sales->links() }}</div>
@endsection
