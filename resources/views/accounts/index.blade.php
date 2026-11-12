@extends('layouts.app')
@section('title','Accounts')
@section('content')
<div class="page-heading"><div><div class="kicker">Finance</div><h1>Receivables & Payables</h1><p class="tagline">One combined party account across all factory names.</p></div><a class="btn secondary" href="{{ route('accounts.bills') }}">View All Bills & Sales</a></div>
<div class="grid"><div class="card metric"><div class="label">Total Customer Receivables</div><div class="value">Rs {{ number_format($receivables,2) }}</div></div><div class="card metric"><div class="label">Total Supplier Payables</div><div class="value">Rs {{ number_format($payables,2) }}</div></div></div>
<div class="card"><h2>Party Balances</h2><div class="table-wrap"><table><thead><tr><th>Party</th><th>Type</th><th class="num">Receivable (PKR)</th><th class="num">Payable (PKR)</th><th>Account</th></tr></thead><tbody>
@forelse($parties as $p)<tr><td>{{ $p->name }}</td><td>{{ ucfirst($p->type) }}</td><td class="num">{{ number_format($p->balances['receivable'],2) }}</td><td class="num">{{ number_format($p->balances['payable'],2) }}</td><td><a href="{{ route('accounts.show',$p) }}">View Running Ledger</a></td></tr>@empty<tr><td colspan="5" class="empty">No parties yet. Add parties in Settings.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
