@extends('layouts.app')
@section('title','Available Fabric Stock')
@section('content')
<div class="page-heading"><div><div class="kicker">Inventory</div><h1>Available Fabric Stock</h1><p class="tagline">Only checked Thaans not currently dispatched or accepted.</p></div><a class="btn" href="{{ route('fabric.index') }}">+ Fabric Checking</a></div>
<div style="margin-bottom:14px"><a class="btn secondary small" href="{{ route('export.stock',request()->only('factory_id')) }}">Export Available Stock CSV</a></div><div class="card"><form method="GET"><div class="actions"><select name="factory_id" style="max-width:300px"><option value="">All Factories</option>@foreach($factories as $f)<option value="{{ $f->id }}" @selected(request('factory_id')==$f->id)>{{ $f->name }}</option>@endforeach</select><button class="btn secondary">Filter</button></div></form></div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>Thaan Number</th><th>Factory</th><th>Quality</th><th>Ownership</th><th>Contract</th><th class="num">Metres</th></tr></thead><tbody>
@forelse($thaans as $t)<tr><td>{{ $t->thaan_no }}</td><td>{{ $t->factory->name }}</td><td>{{ $t->quality->name }}</td><td>{{ $t->ownership==='own'?'Factory Owned':'Job Work' }}</td><td>{{ $t->contract?->reference ?? 'For Stock' }}</td><td class="num">{{ number_format($t->metres,2) }}</td></tr>@empty<tr><td colspan="6" class="empty">No available Thaans recorded.</td></tr>@endforelse</tbody></table></div>{{ $thaans->links() }}</div>
@endsection
