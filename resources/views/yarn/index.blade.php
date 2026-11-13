@extends('layouts.app')
@section('title','Yarn Purchases')
@section('content')
<div class="page-heading"><div><div class="kicker">Material Records</div><h1>Yarn Purchases</h1><p class="tagline">Factory-owned yarn purchases only. Job Work party bags are recorded inside the related contract.</p></div></div>
<div class="card"><h2>Record Yarn Purchase or Receipt</h2><p class="tagline">Supplier purchases update the payable. Customer-delivered bags are recorded against their contract without a payable.</p><form action="{{ route('yarn.purchases.store') }}" method="POST">@csrf<input type="hidden" name="factory_id" id="contractFactoryId"><div class="form-grid">
<div class="field" id="factoryField"><label>Purchasing Factory</label><select name="factory_id" id="purchaseFactory" required><option value="">Select factory</option>@foreach($factories as $f)<option value="{{ $f->id }}">{{ $f->name }}</option>@endforeach<option value="all">All Factories</option></select><div class="hint">All Factories records shared, unassigned yarn stock.</div></div>
<div class="field" id="supplierField"><label>Supplier</label><div class="select-action"><select name="party_id" id="supplierSelect"><option value="">Select supplier</option>@foreach($parties as $p)<option value="{{ $p->id }}">{{ $p->name }}{{ $p->type==='both'?' Â· Customer & Supplier':'' }}</option>@endforeach</select><button class="btn secondary small" type="button" id="showNewSupplier">Create Supplier</button></div></div>
<div class="field" data-mode-field="date"><label>Date Received / Purchase</label><input name="purchased_on" type="date" value="{{ date('Y-m-d') }}" required></div>
<div class="field" data-mode-field="yarn-type"><label>Yarn Type</label><select name="yarn_type" required><option value="warp">Warp</option><option value="weft">Weft</option></select></div>
<div class="field" data-mode-field="contract"><label>Contract (optional)</label><select name="contract_id" id="purchaseContract"><option value="">General stock (supplier purchase)</option>@foreach($contracts as $c)@php
  $eligible=$c->type==='job_work' && $c->status==='active';
  $parta=$c->parta_snapshot['results'] ?? [];
  $customerSource=$c->yarn_supplier==='customer';
  $warpReceived=(float)($customerSource?$c->customer_warp_received:$c->factory_warp_received);
  $weftReceived=(float)($customerSource?$c->customer_weft_received:$c->factory_weft_received);
@endphp<option value="{{ $c->id }}" data-factory="{{ $c->factory_id }}" data-scope="{{ $c->factory_scope ?? 'single' }}" data-eligible="{{ $eligible?'1':'0' }}" data-provider="{{ $c->yarn_supplier }}" data-party="{{ $c->party->name }}" data-warp-remaining="{{ max(0,(float)($parta['warp_bags']??0)-$warpReceived) }}" data-weft-remaining="{{ max(0,(float)($parta['weft_bags']??0)-$weftReceived) }}" data-warp-rate-lb="{{ $c->parta_snapshot['inputs']['warp_yarn_rate'] ?? '' }}" data-weft-rate-lb="{{ $c->parta_snapshot['inputs']['weft_yarn_rate'] ?? '' }}" @disabled(!$eligible)>{{ $c->reference }} - {{ $c->party->name }} - {{ ($c->factory_scope ?? 'single')==='all'?'All Factories':$c->factory->name }}{{ !$eligible?' - unavailable for yarn entries':'' }}</option>@endforeach</select><div class="hint">Choose a factory to narrow the contracts. All Factories shows contracts from every factory. Selecting a contract fills its factory automatically; customer-supplied contracts record bags without creating a supplier payable.</div></div>
<div class="field span-2" id="entryModeNotice" hidden></div>
<div class="field"><label id="referenceLabel">Invoice / Challan Number</label><input name="invoice_no"></div>
<div class="field span-2" id="qualityField" data-supplier-only><label>Yarn Quality</label><div class="select-action"><select name="yarn_quality_id" id="yarnQualitySelect"><option value="">Choose yarn quality</option>@foreach($yarnQualities as $quality)<option value="{{ $quality->id }}">{{ $quality->name }}{{ $quality->count?' · '.$quality->count:'' }}</option>@endforeach</select><button class="btn secondary small" type="button" id="showNewYarnQuality">Create Yarn Quality</button></div><div class="hint">Yarn quality is its own list and is separate from fabric quality.</div></div>
<div class="field" id="bagsField"><label id="bagsLabel">Number of Bags</label><input name="bags" type="number" min="0.001" step="0.001" required><div class="hint">Bag quantity is checked against the remaining contract amount for the selected type.</div></div>
<div class="field" id="rateFields" data-supplier-only><label>Yarn Rate (PKR / lb)</label><input name="rate_per_lb" id="ratePerLb" type="number" min="0" step="0.0001" placeholder="e.g. 600"><div class="hint">Rate per 100 lb bag: Rs <strong id="ratePerBagPreview">0.00</strong></div></div>
<div class="field" id="conesField" hidden><label>Cones per Bag (optional)</label><input name="cones_per_bag" type="number" min="1" max="200" step="1" placeholder="e.g. 24 or 40"><div class="hint">One bag is treated as 100 lbs, whatever the cone count.</div></div>
<div class="field" data-supplier-only><label>Payment Terms (days)</label><input name="payment_terms_days" type="number" min="0" max="365" step="1" placeholder="e.g. 25"><div class="hint">Leave blank if no due date was agreed. 0 means due immediately.</div></div>
<div class="field" data-supplier-only><label>Due Date</label><input id="paymentDuePreview" type="date" readonly><div class="hint">Calculated from the purchase date and payment terms.</div></div>
<div class="field span-2" data-supplier-only><label>Broker / Payment Terms Note</label><input name="payment_terms_note" maxlength="255" placeholder="e.g. Through broker Ali; Sutlej terms net 25 days"></div>
<div class="field span-2" data-mode-field="notes"><label id="notesLabel">Other Notes</label><input name="notes"></div>
</div><div class="input-actions"><button class="btn">Save Purchase + Update Payable</button></div></form></div>
<div class="quick-modal" id="newSupplierPanel" hidden><div class="quick-modal-backdrop" data-close-modal></div><section class="quick-modal-card" role="dialog" aria-modal="true" aria-labelledby="supplierModalTitle"><button class="quick-modal-close" type="button" data-close-modal aria-label="Close">Ã—</button><div class="kicker">New Supplier</div><h2 id="supplierModalTitle">Create Supplier</h2><p class="tagline">Add the supplier here and it will be selected for this purchase.</p><div class="form-grid modal-fields"><div class="field span-2"><label for="newSupplierName">Supplier Name</label><input id="newSupplierName" maxlength="150" placeholder="Supplier name"></div><div class="field"><label for="newSupplierPhone">Phone (optional)</label><input id="newSupplierPhone" maxlength="60"></div><div class="field"><label for="newSupplierOpening">Opening Payable (PKR, optional)</label><input id="newSupplierOpening" type="number" min="0" step="0.01" value="0"></div><div class="field span-2"><label for="newSupplierAddress">Address (optional)</label><input id="newSupplierAddress" maxlength="1000"></div></div><div class="quick-modal-actions"><button class="btn" type="button" id="saveNewSupplier">Create Supplier</button></div><div id="newSupplierError" class="hint" role="alert"></div></section></div>
<div class="quick-modal" id="newYarnQualityPanel" hidden><div class="quick-modal-backdrop" data-close-yarn-quality></div><section class="quick-modal-card" role="dialog" aria-modal="true" aria-labelledby="yarnQualityModalTitle"><button class="quick-modal-close" type="button" data-close-yarn-quality aria-label="Close">×</button><div class="kicker">Yarn Master</div><h2 id="yarnQualityModalTitle">Create Yarn Quality</h2><p class="tagline">Define a yarn quality once, then use it on future purchases.</p><div class="form-grid modal-fields"><div class="field span-2"><label for="newYarnQualityName">Quality Name</label><input id="newYarnQualityName" maxlength="120" placeholder="e.g. Cotton Combed"></div><div class="field"><label for="newYarnQualityCount">Yarn Count / Grade</label><input id="newYarnQualityCount" maxlength="80" placeholder="e.g. 30s"></div><div class="field"><label for="newYarnQualityDescription">Description (optional)</label><input id="newYarnQualityDescription" maxlength="255" placeholder="e.g. Ring spun"></div></div><div class="quick-modal-actions"><button class="btn" type="button" id="saveNewYarnQuality">Create Yarn Quality</button></div><div id="newYarnQualityError" class="hint" role="alert"></div></section></div>
<div class="card"><h2>Yarn Stock by Quality</h2><p class="tagline">Supplier purchases are grouped by yarn quality. Customer-provided bags are grouped separately by owner and factory scope. Contract-linked bags and general stock are shown separately.</p><div class="table-wrap"><table><thead><tr><th>Yarn / Stock Type</th><th>Owner</th><th>Factory Scope</th><th class="num">Warp Bags</th><th class="num">Weft Bags</th><th class="num">Contract Linked</th><th class="num">General Stock</th><th class="num">Total Bags</th></tr></thead><tbody>@forelse($stockByQuality as $row)<tr><td>{{ $row['quality'] }} @if($row['count'])({{ $row['count'] }})@endif</td><td>{{ $row['owner'] }}</td><td>{{ $row['factory'] }}</td><td class="num">{{ number_format($row['warp'],3) }}</td><td class="num">{{ number_format($row['weft'],3) }}</td><td class="num">{{ number_format($row['contract'],3) }}</td><td class="num">{{ number_format($row['general'],3) }}</td><td class="num"><strong>{{ number_format($row['warp']+$row['weft'],3) }}</strong></td></tr>@empty<tr><td colspan="8" class="empty">No yarn stock has been recorded yet.</td></tr>@endforelse</tbody></table></div><p class="hint">Contract-linked shows yarn purchases assigned to active or historical contracts; general stock is not assigned to a contract. Factory and customer yarn are shown separately; production issues are not tracked.</p></div>
<div class="card"><h2>Yarn Received Against Contracts</h2><p class="tagline">Shows received Warp and Weft bags by contract and owner. Customer-supplied bags have no supplier yarn quality or price.</p><div class="table-wrap"><table><thead><tr><th>Contract</th><th>Customer</th><th>Yarn / Stock Type</th><th>Owner</th><th>Factory Scope</th><th class="num">Warp Bags</th><th class="num">Weft Bags</th><th class="num">Total Bags</th></tr></thead><tbody>@forelse($contractStock as $row)<tr><td><a href="{{ route('contracts.show', ['contract' => $row['contract_id']]) }}">{{ $row['contract'] }}</a></td><td>{{ $row['party'] }}</td><td>{{ $row['quality'] }} @if($row['count'])({{ $row['count'] }})@endif</td><td>{{ $row['owner'] }}</td><td>{{ $row['factory'] }}</td><td class="num">{{ number_format($row['warp'],3) }}</td><td class="num">{{ number_format($row['weft'],3) }}</td><td class="num"><strong>{{ number_format($row['warp']+$row['weft'],3) }}</strong></td></tr>@empty<tr><td colspan="8" class="empty">No yarn entries are linked to a contract.</td></tr>@endforelse</tbody></table></div></div>
<div class="card"><div class="page-heading"><h2>Recent Supplier Purchases</h2><a class="btn secondary small" href="{{ route('export.transactions',['kind'=>'purchases']) }}">Export CSV</a></div><div class="table-wrap"><table><thead><tr><th>Date</th><th>Factory</th><th>Supplier</th><th>Description</th><th class="num">Bags</th><th>Payment Terms / Due</th><th class="num">Total (PKR)</th><th>Action</th></tr></thead><tbody>
@forelse($purchases as $p)<tr><td>{{ $p->purchased_on }}</td><td>{{ ($p->factory_scope ?? 'single')==='all' ? 'All Factories' : $p->factory->name }}</td><td>{{ $p->party->name }}</td><td>{{ $p->yarn_type?ucfirst($p->yarn_type):'Unclassified' }} · {{ $p->yarnQuality?->name ?? $p->yarn_description }}{{ $p->yarnQuality?->count ? ' · '. $p->yarnQuality->count : '' }} @if($p->contract)<div class="small muted">{{ $p->contract->reference }}</div>@endif</td><td class="num">{{ number_format($p->bags,3) }}</td><td>@if($p->payment_due_on){{ $p->payment_terms_days }} days · {{ $p->payment_due_on }}@else Not specified @endif @if($p->payment_terms_note)<div class="small muted">{{ $p->payment_terms_note }}</div>@endif<div class="small muted">{{ number_format(max(0,(float)$p->total-(float)($p->linked_paid_total??0)),2) }} PKR remaining</div></td><td class="num">{{ number_format($p->total,2) }} @if($p->voided_at)<div class="pill warn">Reversed</div>@endif</td><td><a href="{{ route('print.purchase',$p) }}" target="_blank">Print</a>
@if(!$p->voided_at && auth()->user()->role==='admin')<form method="POST" action="{{ route('corrections.purchase',$p) }}" onsubmit="return confirm('Reverse this purchase and supplier payable?')">@csrf<input type="text" name="reason" minlength="8" required placeholder="Reason for reversal" style="min-width:170px"><button class="btn small secondary">Reverse</button></form>@endif</td></tr>@empty<tr><td colspan="8" class="empty">No yarn purchases yet.</td></tr>@endforelse</tbody></table></div>{{ $purchases->links() }}</div>
<div class="card"><h2>Customer Yarn Bags Received</h2><div class="table-wrap"><table><thead><tr><th>Date Received</th><th>Factory Scope</th><th>Customer</th><th>Contract</th><th>Warp / Weft</th><th class="num">Bags</th><th class="num">Cones / Bag</th><th>Delivery Reference</th></tr></thead><tbody>@forelse($customerReceipts as $receipt)<tr><td>{{ $receipt->received_on }}</td><td>{{ ($receipt->factory_scope ?? 'single')==='all'?'All Factories':($receipt->factory?->name ?? $receipt->contract->factory->name) }}</td><td>{{ $receipt->contract->party->name }}</td><td><a href="{{ route('contracts.show',$receipt->contract) }}">{{ $receipt->contract->reference }}</a></td><td>{{ ucfirst($receipt->yarn_type) }}</td><td class="num">{{ number_format($receipt->bags,3) }}</td><td class="num">{{ $receipt->cones_per_bag ?? '-' }}</td><td>{{ $receipt->reference ?? '-' }}</td></tr>@empty<tr><td colspan="8" class="empty">No customer-supplied yarn bags have been recorded.</td></tr>@endforelse</tbody></table></div></div>
@push('scripts')<script>
(() => {
  const modal=document.getElementById('newSupplierPanel'),select=document.getElementById('supplierSelect');
  const qualityModal=document.getElementById('newYarnQualityPanel'),qualitySelect=document.getElementById('yarnQualitySelect');
  const factorySelect=document.getElementById('purchaseFactory'),contractSelect=document.getElementById('purchaseContract');
  const purchaseDate=document.querySelector('input[name="purchased_on"]'),terms=document.querySelector('input[name="payment_terms_days"]'),duePreview=document.getElementById('paymentDuePreview');
  const supplierField=document.getElementById('supplierField'),supplierSelect=document.getElementById('supplierSelect'),rateInput=document.getElementById('ratePerLb');
  const factoryField=document.getElementById('factoryField'),factorySelectField=factoryField.querySelector('select');
  const contractFactoryId=document.getElementById('contractFactoryId');
  const rateLabel=document.querySelector('#rateFields label'),ratePreview=document.getElementById('ratePerBagPreview'),conesField=document.getElementById('conesField');
  const bagsInput=document.querySelector('input[name="bags"]'),yarnTypeSelect=document.querySelector('select[name="yarn_type"]');
  const modeNotice=document.getElementById('entryModeNotice'),referenceLabel=document.getElementById('referenceLabel');
  const bagsLabel=document.getElementById('bagsLabel'),notesLabel=document.getElementById('notesLabel');
  const entryButton=document.querySelector('.input-actions button');
  function updateDuePreview(){if(!purchaseDate.value||terms.value===''){duePreview.value='';return;}const d=new Date(purchaseDate.value+'T00:00:00');d.setDate(d.getDate()+Number(terms.value));duePreview.value=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');}
  purchaseDate.addEventListener('change',updateDuePreview);terms.addEventListener('input',updateDuePreview);updateDuePreview();
  document.body.append(modal);
  document.body.append(qualityModal);
  function filterContracts(){
    const chosen=factorySelect.value;
    [...contractSelect.options].slice(1).forEach(option=>{
      const compatible=chosen==='all'||!chosen||option.dataset.scope==='all'||option.dataset.factory===chosen;
      option.hidden=!compatible;
      option.disabled=!compatible||option.dataset.eligible!=='1';
      if(!compatible&&option.selected){contractSelect.value='';updateEntryMode()};
    });
  }
  function updateRatePreview(){const rate=Number(rateInput.value||0);ratePreview.textContent=(rate*100).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});}
  rateInput.addEventListener('input',updateRatePreview);updateRatePreview();
  function setFieldMode(selector, hidden){
    document.querySelectorAll(selector).forEach(field=>{
      field.hidden=hidden;
      field.querySelectorAll('input,select,textarea,button').forEach(control=>{
        if(control.type!=='hidden')control.disabled=hidden;
      });
    });
  }
  function updateEntryMode(prefillRate=false){
    const option=contractSelect.selectedOptions[0],customerMode=option?.dataset.eligible==='1'&&option.dataset.provider==='customer';
    const contractMode=option?.dataset.eligible==='1';
    setFieldMode('[data-mode-field="date"]',false);
    setFieldMode('[data-mode-field="yarn-type"]',false);
    setFieldMode('[data-mode-field="contract"]',false);
    setFieldMode('[data-mode-field="rate"]',false);
    setFieldMode('[data-mode-field="notes"]',false);
    setFieldMode('[data-supplier-only]',customerMode);
    setFieldMode('#conesField',!customerMode);
    setFieldMode('#factoryField',customerMode);
    setFieldMode('#supplierField',customerMode);
    setFieldMode('#qualityField',customerMode);
    supplierSelect.required=!customerMode;
    factorySelectField.required=!customerMode;
    contractFactoryId.disabled=!customerMode;
    contractFactoryId.value=customerMode?(option.dataset.scope==='all'?'all':option.dataset.factory):'';
    rateInput.required=!customerMode;
    document.getElementById('yarnQualitySelect').required=!customerMode;
    bagsInput.required=true;
    referenceLabel.textContent=customerMode?'Delivery Reference':'Invoice / Challan Number';
    bagsLabel.textContent=customerMode?'Bags Received':'Number of Bags';
    notesLabel.textContent=customerMode?'Delivery Notes':'Other Notes';
    rateLabel.textContent='Supplier Yarn Rate (PKR / lb)';
    modeNotice.hidden=!contractMode;
    if(customerMode){
      const remainder=yarnTypeSelect.value==='warp'?option.dataset.warpRemaining:option.dataset.weftRemaining;
      modeNotice.textContent='Customer '+option.dataset.party+' supplies this yarn. No supplier purchase, quality, or payable is recorded. Remaining '+yarnTypeSelect.value+': '+Number(remainder).toFixed(2)+' bags.';
    }else if(contractMode){
      modeNotice.textContent='Factory supplies yarn. Select the supplier and yarn quality; the purchase creates a supplier payable.';
    }
    if(prefillRate&&!customerMode){const suggested=yarnTypeSelect.value==='warp'?option?.dataset.warpRateLb:option?.dataset.weftRateLb;rateInput.value=suggested||'';updateRatePreview();}
    if(option?.dataset.eligible==='1'){
      const remaining=Number(yarnTypeSelect.value==='warp'?option.dataset.warpRemaining:option.dataset.weftRemaining);
      bagsInput.max=String(remaining);bagsInput.nextElementSibling.textContent=`Remaining ${yarnTypeSelect.value}: ${remaining.toFixed(2)} bags for this contract. The quantity may be less, but not more.`;
    }else{bagsInput.removeAttribute('max');bagsInput.nextElementSibling.textContent='Bag quantity is checked against the remaining contract amount for the selected type.';}
    if(entryButton)entryButton.textContent=customerMode?'Record Customer Bags Received':(contractMode?'Record Supplier Yarn for Contract':'Save Purchase + Update Payable');
  }
  factorySelect.addEventListener('change',()=>{filterContracts();updateEntryMode()});
  contractSelect.addEventListener('change',()=>{
    const option=contractSelect.selectedOptions[0];
    if(option?.dataset.eligible==='1'){factorySelect.value=option.dataset.scope==='all'?'all':option.dataset.factory;if(option.dataset.provider==='customer')supplierSelect.value='';}
    filterContracts();
    updateEntryMode(true);
  });
  yarnTypeSelect.addEventListener('change',()=>updateEntryMode(true));

  filterContracts();
  updateEntryMode();
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
  function closeQuality(){qualityModal.hidden=true;document.body.classList.remove('modal-open')}
  document.getElementById('showNewYarnQuality').addEventListener('click',()=>{qualityModal.hidden=false;document.body.classList.add('modal-open');document.getElementById('newYarnQualityName').focus()});
  qualityModal.querySelectorAll('[data-close-yarn-quality]').forEach(el=>el.addEventListener('click',closeQuality));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!qualityModal.hidden)closeQuality()});
  document.getElementById('saveNewYarnQuality').addEventListener('click',async()=>{
    const button=document.getElementById('saveNewYarnQuality'),error=document.getElementById('newYarnQualityError'),name=document.getElementById('newYarnQualityName').value.trim();
    error.textContent='';if(!name){error.textContent='Enter a yarn quality name.';return;}button.disabled=true;
    try{
      const response=await fetch(@json(route('yarn.qualities.store')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({name,count:document.getElementById('newYarnQualityCount').value.trim()||null,description:document.getElementById('newYarnQualityDescription').value.trim()||null})});
      const data=await response.json();if(!response.ok)throw new Error(data.message||Object.values(data.errors||{}).flat().join(' ')||'Could not create yarn quality.');
      const q=data.yarn_quality,label=q.name+(q.count?' · '+q.count:'');qualitySelect.add(new Option(label,q.id,true,true));closeQuality();['newYarnQualityName','newYarnQualityCount','newYarnQualityDescription'].forEach(id=>document.getElementById(id).value='');
    }catch(e){error.textContent=e.message}finally{button.disabled=false}
  });
})();
</script>@endpush
@endsection
