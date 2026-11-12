@extends('layouts.app')
@section('title','Bills & Fabric Sales')
@section('content')
<div class="page-heading"><div><div class="kicker">Accounts</div><h1>Bills & Fabric Sales</h1><p class="tagline">Conversion bills and fabric sales are generated from Pakki accepted metres.</p></div><a class="btn secondary" href="{{ route('export.transactions',['kind'=>'bills']) }}">Export All CSV</a></div>
<div class="card"><form method="GET" action="{{ route('accounts.bills') }}" class="form-grid">
<div class="field"><label>Party</label><select name="party_id"><option value="">All parties</option>@foreach($parties as $p)<option value="{{ $p->id }}" @selected(request('party_id')==$p->id)>{{ $p->name }}</option>@endforeach</select></div>
<div class="field"><label>Factory</label><select name="factory_id"><option value="">All factories</option>@foreach($factories as $f)<option value="{{ $f->id }}" @selected(request('factory_id')==$f->id)>{{ $f->name }}</option>@endforeach</select></div>
<div class="field"><label>Billing Type</label><select name="type"><option value="">Both</option><option value="conversion" @selected(request('type')==='conversion')>Job Work Conversion</option><option value="fabric_sale" @selected(request('type')==='fabric_sale')>Own Fabric Sale</option></select></div>
<div class="field"><label>&nbsp;</label><button class="btn">Apply Filters</button></div></form></div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>Bill</th><th>Date</th><th>Party</th><th>Factory</th><th>Type</th><th class="num">Accepted m</th><th class="num">Total PKR</th><th>Print</th></tr></thead><tbody>
@forelse($bills as $b)<tr><td>{{ $b->reference }}</td><td>{{ $b->billed_on }}</td><td><a href="{{ route('accounts.show',$b->party) }}">{{ $b->party->name }}</a></td><td>{{ $b->factory->name }}</td><td>{{ $b->type==='conversion'?'Job Work':'Fabric Sale' }}</td><td class="num">{{ number_format($b->accepted_metres,2) }}</td><td class="num">{{ number_format($b->total,2) }}</td><td><a href="{{ route('print.bill',$b) }}" target="_blank">Print/PDF</a></td></tr>@empty<tr><td colspan="8" class="empty">No bills generated yet. Billing starts after Pakki acceptance.</td></tr>@endforelse
</tbody></table></div>{{ $bills->links() }}</div>
@endsection
