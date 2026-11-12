@extends('layouts.app')
@section('title','Yarn Purchases')
@section('content')
<div class="page-heading"><div><div class="kicker">Material Records</div><h1>Yarn Purchases</h1><p class="tagline">Factory-owned yarn purchases only. Job Work party bags are recorded inside the related contract.</p></div></div>
<div class="card"><h2>New Yarn Purchase</h2><form action="{{ route('yarn.purchases.store') }}" method="POST">@csrf<div class="form-grid">
<div class="field"><label>Purchasing Factory</label><select name="factory_id" required><option value="">Select factory</option>@foreach($factories as $f)<option value="{{ $f->id }}">{{ $f->name }}</option>@endforeach</select></div>
<div class="field"><label>Supplier</label><select name="party_id" required><option value="">Select supplier</option>@foreach($parties as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
<div class="field"><label>Purchase Date</label><input name="purchased_on" type="date" value="{{ date('Y-m-d') }}" required></div>
<div class="field"><label>Invoice / Challan Number</label><input name="invoice_no"></div>
<div class="field span-2"><label>Yarn Description / Count</label><input name="yarn_description" placeholder="e.g. 30s cotton yarn" required></div>
<div class="field"><label>Number of Bags</label><input name="bags" type="number" min="0.001" step="0.001" required></div>
<div class="field"><label>Cones per Bag (optional)</label><input name="cones_per_bag" type="number" min="1" step="1" placeholder="e.g. 24 or 40"><div class="hint">Every bag stays 100 lbs.</div></div><div class="field"><label>Purchase Rate per Bag (PKR)</label><input name="rate_per_bag" type="number" min="0" step="0.0001" required></div>
<div class="field span-2"><label>Notes</label><input name="notes"></div>
</div><div class="input-actions"><button class="btn">Save Purchase + Update Payable</button></div></form></div>
<div class="card"><div class="page-heading"><h2>Recent Purchases</h2><a class="btn secondary small" href="{{ route('export.transactions',['kind'=>'purchases']) }}">Export CSV</a></div><div class="table-wrap"><table><thead><tr><th>Date</th><th>Factory</th><th>Supplier</th><th>Description</th><th class="num">Bags</th><th>Cones / Bag</th><th class="num">Total (PKR)</th><th>Action</th></tr></thead><tbody>
@forelse($purchases as $p)<tr><td>{{ $p->purchased_on }}</td><td>{{ $p->factory->name }}</td><td>{{ $p->party->name }}</td><td>{{ $p->yarn_description }}</td><td class="num">{{ number_format($p->bags,3) }}</td><td>{{ $p->cones_per_bag ?? "—" }}</td><td class="num">{{ number_format($p->total,2) }} @if($p->voided_at)<div class="pill warn">Reversed</div>@endif</td><td><a href="{{ route('print.purchase',$p) }}" target="_blank">Print</a>
@if(!$p->voided_at && auth()->user()->role==='admin')<form method="POST" action="{{ route('corrections.purchase',$p) }}" onsubmit="return confirm('Reverse this purchase and supplier payable?')">@csrf<input type="text" name="reason" minlength="8" required placeholder="Reason for reversal" style="min-width:170px"><button class="btn small secondary">Reverse</button></form>@endif</td></tr>@empty<tr><td colspan="8" class="empty">No yarn purchases yet.</td></tr>@endforelse</tbody></table></div>{{ $purchases->links() }}</div>
@endsection
