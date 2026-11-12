@extends('layouts.app')
@section('title','Kachi / Pakki')
@section('content')
<div class="page-heading"><div><div class="kicker">Fabric Dispatch & Acceptance</div><h1>Kachi & Pakki Parchi</h1><p class="tagline">Only Pakki accepted metres generate a bill.</p></div></div>
<div class="card"><h2>Create Kachi Parchi</h2><form method="POST" action="{{ route('dispatch.kachi.store') }}">@csrf<div class="form-grid">
<div class="field"><label>Dispatch Type</label><select name="kind" id="dispatchKind"><option value="job_work">Job Work / Conversion</option><option value="fabric_sale">Factory-Owned Fabric Sale</option></select></div>
<div class="field"><label>Dispatch Date</label><input name="dispatched_on" type="date" value="{{ date('Y-m-d') }}" required></div>
<div class="field"><label>Factory</label><select name="factory_id" required id="factory"><option value="">Select factory</option>@foreach($factories as $f)<option value="{{ $f->id }}">{{ $f->name }}</option>@endforeach</select></div>
<div class="field"><label>Customer</label><select name="party_id" required><option value="">Select customer</option>@foreach($parties as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
<div class="field"><label>Contract</label><select name="contract_id" id="contract" ><option value="">No contract (own stock sale only)</option>@foreach($contracts as $c)<option value="{{ $c->id }}" data-type="{{ $c->type }}" data-party="{{ $c->party_id }}" data-factory="{{ $c->factory_id }}" data-scope="{{ $c->factory_scope ?? 'single' }}">{{ $c->reference }} · {{ $c->party->name }}{{ ($c->factory_scope ?? 'single')==='all'?' · All Factories':'' }}</option>@endforeach</select></div>
<div class="field" id="rateField" style="display:none"><label>Sale Rate / Metre (for stock sale)</label><input type="number" name="rate_per_metre" min="0" step="0.0001" placeholder="Only if no contract"></div>
<div class="field span-2"><label>Available Thaans for this Dispatch</label><div class="select-list">@forelse($thaans as $t)<label class="thaan-item" data-factory="{{ $t->factory_id }}" data-kind="{{ $t->ownership }}" data-contract="{{ $t->contract_id ?? '' }}" data-quality="{{ $t->fabric_quality_id }}" data-metres="{{ $t->metres }}"><input type="checkbox" name="thaan_ids[]" value="{{ $t->id }}"> <span>{{ $t->thaan_no }} · {{ number_format($t->metres,2) }} m · {{ $t->quality->name }} · {{ $t->factory->name }} · {{ $t->contract_id?'Contract '.$t->contract_id:'Own Stock' }}</span></label>@empty<div class="empty">No available Thaans. Enter checked fabric first.</div>@endforelse</div><div class="hint">Select Thaans from one factory and one quality. Unallocated own-stock fabric can also be sold against a newly created fabric-rate contract.</div><div id="dispatchSelectionSummary" class="note" aria-live="polite">0 Thaans selected</div></div>
</div><div class="input-actions"><button class="btn">Create Kachi Parchi</button></div></form></div>
<div class="card"><h2>Kachi / Pakki Register</h2><div class="table-wrap"><table><thead><tr><th>Kachi</th><th>Date</th><th>Party</th><th>Factory</th><th>Type</th><th>Pakki</th><th>Status</th><th>Bill</th></tr></thead><tbody>
@forelse($kachis as $k)<tr><td><a href="{{ route('dispatch.show',$k) }}">{{ $k->reference }}</a></td><td>{{ $k->dispatched_on }}</td><td>{{ $k->party->name }}</td><td>{{ $k->factory->name }}</td><td>{{ $k->kind==='job_work'?'Job Work':'Fabric Sale' }}</td><td>{{ $k->voided_at?'Cancelled':($k->pakki?'Recorded':'Awaiting') }}</td><td><span class="pill {{ $k->status==='pending_returns'?'warn':'' }}">{{ $k->voided_at?'Cancelled':ucwords(str_replace('_',' ',$k->status)) }}</span></td><td>{{ $k->bill?->reference ?? '—' }}</td></tr>@empty<tr><td colspan="8" class="empty">No parchis yet.</td></tr>@endforelse</tbody></table></div>{{ $kachis->links() }}</div>
@push('scripts')<script>
const kind=document.getElementById('dispatchKind');
const contract=document.getElementById('contract');
const factory=document.getElementById('factory');
const party=document.querySelector('select[name="party_id"]');
const rateField=document.getElementById('rateField');
const stockItems=[...document.querySelectorAll('.thaan-item')];
function filter(){
  const own=kind.value==='fabric_sale';
  rateField.style.display=own?'flex':'none';
  contract.required=!own;
  [...contract.options].forEach(o=>o.hidden=!!o.dataset.type&&o.dataset.type!==(own?'fabric_rate':'job_work'));
  if(contract.selectedOptions[0]?.hidden)contract.value='';
  const selected=contract.selectedOptions[0];
  stockItems.forEach(el=>{
    const eligible=(!factory.value||el.dataset.factory===factory.value)&&
      el.dataset.kind===(own?'own':'job_work')&&
      (contract.value?(el.dataset.contract===contract.value||(own&&el.dataset.contract==='')):
        own&&el.dataset.contract==='');
    el.style.display=eligible?'flex':'none';
    if(!eligible)el.querySelector('input').checked=false;
  });
  updateSelection();
}
function updateSelection(){
  const selected=stockItems.filter(x=>x.querySelector('input').checked);
  const total=selected.reduce((s,x)=>s+Number(x.dataset.metres||0),0);
  const qualities=new Set(selected.map(x=>x.dataset.quality));
  const summary=document.getElementById('dispatchSelectionSummary');
  if(summary)summary.textContent=selected.length+' Thaans selected · '+total.toFixed(2)+' m'+(qualities.size>1?' · MIXED QUALITIES: select only one':'');
}
contract.addEventListener('change',()=>{
  const chosen=contract.selectedOptions[0];
  if(chosen?.dataset.factory&&chosen.dataset.scope!=='all')factory.value=chosen.dataset.factory;
  if(chosen?.dataset.party)party.value=chosen.dataset.party;
  filter();
});
[kind,factory].forEach(el=>el.addEventListener('change',filter));
stockItems.forEach(x=>x.querySelector('input').addEventListener('change',updateSelection));
filter();
</script>@endpush
@endsection
