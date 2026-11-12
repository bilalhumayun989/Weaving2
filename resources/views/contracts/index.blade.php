@extends('layouts.app')
@section('title','Contracts')
@section('content')
<div class="page-heading"><div><div class="kicker">Commercial Agreements</div><h1>Contracts</h1><p class="tagline">Job Work and Fabric Rate contracts.</p></div><a class="btn" href="{{ route('contracts.create') }}">+ New Contract</a></div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>Reference</th><th>Type</th><th>Party</th><th>Factory</th><th>Quality</th><th>Yarn Supplier</th><th class="num">Required Bags</th><th class="num">Customer Bags Received</th><th>Status</th></tr></thead><tbody>
@forelse($contracts as $c)<tr><td><a href="{{ route('contracts.show',$c) }}">{{ $c->reference }}</a></td><td><span class="pill {{ $c->type==='job_work'?'':'dark' }}">{{ $c->type==='job_work'?'Job Work':'Fabric Rate' }}</span></td><td>{{ $c->party->name }}</td><td>{{ $c->factory->name }}</td><td>{{ $c->quality->name }}</td><td>{{ $c->type==='job_work'?ucfirst($c->yarn_supplier):'Factory' }}</td><td class="num">{{ $c->type==='job_work'?number_format($c->required_bags,3):'—' }}</td><td class="num">{{ $c->type==='job_work' && $c->yarn_supplier==='customer'?number_format($c->receipts_sum_bags ?? 0,3):'—' }}</td><td>{{ ucfirst($c->status) }}</td></tr>@empty<tr><td colspan="9" class="empty">No contracts entered. <a href="{{ route('contracts.create') }}">Create the first one.</a></td></tr>@endforelse
</tbody></table></div>{{ $contracts->links() }}</div>
@endsection
