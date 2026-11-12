@extends('layouts.app')
@section('title','Yarn Purchases')
@section('content')
<div class="page-heading"><div><div class="kicker">Material Records</div><h1>Yarn Purchases</h1><p class="tagline">Factory-owned yarn purchases only. Job Work party bags are recorded inside the related contract.</p></div></div>
<div class="card"><h2>New Yarn Purchase</h2><form action="{{ route('yarn.purchases.store') }}" method="POST">@csrf<div class="form-grid">
<div class="field"><label>Purchasing Factory</label><select name="factory_id" required><option value="">Select factory</option>@foreach($factories as $f)<option value="{{ $f->id }}">{{ $f->name }}</option>@endforeach</select></div>
<div class="field"><label>Supplier</label><div class="select-action"><select name="party_id" id="supplierSelect" required><option value="">Select supplier</option>@foreach($parties as $p)<option value="{{ $p->id }}">{{ $p->name }}{{ $p->type==='both'?' Â· Customer & Supplier':'' }}</option>@endforeach</select><button class="btn secondary small" type="button" id="showNewSupplier">Create Supplier</button></div></div>
<div class="field"><label>Date Received / Purchase</label><input name="purchased_on" type="date" value="{{ date('Y-m-d') }}" required></div>
<div class="field"><label>Yarn Type</label><select name="yarn_type" required><option value="warp">Warp</option><option value="weft">Weft</option></select></div>
<div class="field"><label>Job Work Contract (optional)</label><select name="contract_id"><option value="">General factory stock</option>@foreach($contracts as $c)<option value="{{ $c->id }}">{{ $c->reference }} Â· {{ $c->party->name }}</option>@endforeach</select><div class="hint">Choose a factory-yarn contract to count these bags toward its requirement.</div></div>
<div class="field"><label>Invoice / Challan Number</label><input name="invoice_no"></div>
<div class="field span-2"><label>Yarn Description / Count</label><input name="yarn_description" placeholder="e.g. 30s cotton yarn" required></div>
<div class="field"><label>Number of Bags</label><input name="bags" type="number" min="0.001" step="0.001" required></div>
<div class="field"><label>Cones per Bag (optional)</label><input name="cones_per_bag" type="number" min="1" step="1" placeholder="e.g. 24 or 40"><div class="hint">Every bag stays 100 lbs.</div></div><div class="field"><label>Purchase Rate per Bag (PKR)</label><input name="rate_per_bag" type="number" min="0" step="0.0001" required></div>
<div class="field span-2"><label>Notes</label><input name="notes"></div>
</div><div class="input-actions"><button class="btn">Save Purchase + Update Payable</button></div></form></div>
<div class="quick-modal" id="newSupplierPanel" hidden><div class="quick-modal-backdrop" data-close-modal></div><section class="quick-modal-card" role="dialog" aria-modal="true" aria-labelledby="supplierModalTitle"><button class="quick-modal-close" type="button" data-close-modal aria-label="Close">Ã—</button><div class="kicker">New Supplier</div><h2 id="supplierModalTitle">Create Supplier</h2><p class="tagline">Add the supplier here and it will be selected for this purchase.</p><div class="form-grid modal-fields"><div class="field span-2"><label for="newSupplierName">Supplier Name</label><input id="newSupplierName" maxlength="150" placeholder="Supplier name"></div><div class="field"><label for="newSupplierPhone">Phone (optional)</label><input id="newSupplierPhone" maxlength="60"></div><div class="field"><label for="newSupplierOpening">Opening Payable (PKR, optional)</label><input id="newSupplierOpening" type="number" min="0" step="0.01" value="0"></div><div class="field span-2"><label for="newSupplierAddress">Address (optional)</label><input id="newSupplierAddress" maxlength="1000"></div></div><div class="quick-modal-actions"><button class="btn" type="button" id="saveNewSupplier">Create Supplier</button></div><div id="newSupplierError" class="hint" role="alert"></div></section></div>
<div class="card"><div class="page-heading"><h2>Recent Purchases</h2><a class="btn secondary small" href="{{ route('export.transactions',['kind'=>'purchases']) }}">Export CSV</a></div><div class="table-wrap"><table><thead><tr><th>Date</th><th>Factory</th><th>Supplier</th><th>Description</th><th class="num">Bags</th><th>Cones / Bag</th><th class="num">Total (PKR)</th><th>Action</th></tr></thead><tbody>
@forelse($purchases as $p)<tr><td>{{ $p->purchased_on }}</td><td>{{ $p->factory->name }}</td><td>{{ $p->party->name }}</td><td>{{ $p->yarn_type?ucfirst($p->yarn_type):'Unclassified' }} · {{ $p->yarn_description }} @if($p->contract)<div class="small muted">{{ $p->contract->reference }}</div>@endif</td><td class="num">{{ number_format($p->bags,3) }}</td><td>{{ $p->cones_per_bag ?? "â€”" }}</td><td class="num">{{ number_format($p->total,2) }} @if($p->voided_at)<div class="pill warn">Reversed</div>@endif</td><td><a href="{{ route('print.purchase',$p) }}" target="_blank">Print</a>
@if(!$p->voided_at && auth()->user()->role==='admin')<form method="POST" action="{{ route('corrections.purchase',$p) }}" onsubmit="return confirm('Reverse this purchase and supplier payable?')">@csrf<input type="text" name="reason" minlength="8" required placeholder="Reason for reversal" style="min-width:170px"><button class="btn small secondary">Reverse</button></form>@endif</td></tr>@empty<tr><td colspan="8" class="empty">No yarn purchases yet.</td></tr>@endforelse</tbody></table></div>{{ $purchases->links() }}</div>
@push('scripts')<script>
(() => {
  const modal=document.getElementById('newSupplierPanel'),select=document.getElementById('supplierSelect');
  document.body.append(modal);
  function close(){modal.hidden=true;document.body.classList.remove('modal-open')}
  document.getElementById('showNewSupplier').addEventListener('click',()=>{modal.hidden=false;document.body.classList.add('modal-open');document.getElementById('newSupplierName').focus()});
  modal.querySelectorAll('[data-close-modal]').forEach(el=>el.addEventListener('click',close));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!modal.hidden)close()});
  document.getElementById('saveNewSupplier').addEventListener('click',async()=>{
    const button=document.getElementById('saveNewSupplier'),error=document.getElementById('newSupplierError'),name=document.getElementById('newSupplierName').value.trim();
    error.textContent='';if(!name){error.textContent='Enter a supplier name.';return;}button.disabled=true;
    try{
      const response=await fetch(@json(route('parties.store')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({name,type:'supplier',phone:document.getElementById('newSupplierPhone').value||null,address:document.getElementById('newSupplierAddress').value||null,opening_payable:document.getElementById('newSupplierOpening').value||0})});
      const data=await response.json();if(!response.ok)throw new Error(data.message||Object.values(data.errors||{}).flat().join(' ')||'Could not create supplier.');
      const supplier=data.party,option=new Option(supplier.name,supplier.id,true,true);select.add(option);close();['newSupplierName','newSupplierPhone','newSupplierAddress'].forEach(id=>document.getElementById(id).value='');document.getElementById('newSupplierOpening').value='0';
    }catch(e){error.textContent=e.message}finally{button.disabled=false}
  });
})();
</script>@endpush
@endsection
