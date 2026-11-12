@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="page-heading"><div><div class="kicker">Overview</div><h1>Business Dashboard</h1><p class="tagline">A single view across all three factory names.</p></div><a class="btn" href="{{ route('fabric.index') }}">+ Enter Checked Fabric</a></div>
<div class="grid grid-4">
 <div class="card metric"><div class="label">Customer Receivables</div><div class="value">Rs {{ number_format($receivables,0) }}</div><div class="detail">Combined party balances</div></div>
 <div class="card metric"><div class="label">Supplier Payables</div><div class="value">Rs {{ number_format($payables,0) }}</div><div class="detail">Combined supplier balances</div></div>
 <div class="card metric"><div class="label">Available Fabric</div><div class="value">{{ number_format($availableMetres,2) }} m</div><div class="detail">{{ $availableThaans }} available Thaans</div></div>
 <div class="card metric"><div class="label">Active Contracts</div><div class="value">{{ $activeContracts }}</div><div class="detail">{{ $factoryCount }} factories configured</div></div>
</div>
<div class="grid">
 <div class="card"><h2>Today's Quick Actions</h2><div class="actions"><a class="btn secondary" href="{{ route('contracts.create') }}">New Contract</a><a class="btn secondary" href="{{ route('yarn.index') }}">Yarn Purchase</a><a class="btn secondary" href="{{ route('fabric.index') }}">Fabric Checking</a><a class="btn secondary" href="{{ route('dispatch.index') }}">Kachi / Pakki</a><a class="btn secondary" href="{{ route('accounts.index') }}">Receive Payment</a></div><p class="inline-note">No production-entry step. Stock comes directly from checked Thaan measurements.</p></div>
 <div class="card"><h2>Attention Needed</h2><div class="grid"><div><div class="label muted small">Kachis awaiting Pakki</div><div class="metric value">{{ $awaitingPakki }}</div></div><div><div class="label muted small">Pakki awaiting returned Thaans</div><div class="metric value">{{ $pendingReturns }}</div></div></div></div>
</div>
<div class="card"><h2>Recent Bills</h2><div class="table-wrap"><table><thead><tr><th>Bill</th><th>Party</th><th>Factory</th><th>Type</th><th class="num">Amount</th></tr></thead><tbody>
@forelse($recentBills as $bill)<tr><td><a href="{{ route('print.bill',$bill) }}" target="_blank">{{ $bill->reference }}</a></td><td>{{ $bill->party->name }}</td><td>{{ $bill->factory->name }}</td><td>{{ ucfirst(str_replace('_',' ',$bill->type)) }}</td><td class="num">Rs {{ number_format($bill->total,2) }}</td></tr>@empty<tr><td colspan="5" class="empty">No bills yet.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
