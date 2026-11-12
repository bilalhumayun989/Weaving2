@extends('layouts.app')
@section('title','New Contract')
@section('content')
<div class="page-heading"><div><div class="kicker">Contracts</div><h1>Create a Contract</h1><p class="tagline">Job Work now calculates required bags directly from the approved Fabric Costing worksheet.</p></div><a class="btn secondary" href="{{ route('contracts.index') }}">Back to Contracts</a></div>
<div class="card"><form action="{{ route('contracts.store') }}" method="POST">@csrf
<div class="form-grid">
<div class="field"><label>Contract Type</label><select name="type" id="contractType" required><option value="job_work" @selected(old('type','job_work')==='job_work')>Job Work — party supplies yarn</option><option value="fabric_rate" @selected(old('type')==='fabric_rate')>Fabric Rate — factory supplies yarn</option></select></div>
<div class="field"><label>Contract Date</label><input name="contract_date" type="date" value="{{ old('contract_date',date('Y-m-d')) }}" required></div>
<div class="field"><label>Factory</label><select required name="factory_id"><option value="">Choose factory</option>@foreach($factories as $f)<option value="{{ $f->id }}" @selected(old('factory_id')==$f->id)>{{ $f->name }}</option>@endforeach</select></div>
<div class="field"><label>Party / Buyer</label><div class="select-action"><select required name="party_id"><option value="">Choose party</option>@foreach($parties as $p)<option value="{{ $p->id }}" @selected(old('party_id')==$p->id)>{{ $p->name }} · {{ ucfirst($p->type) }}</option>@endforeach</select><button class="btn secondary small" type="button" id="showNewParty">Create Customer / Party</button></div></div>
<div class="field"><label>Fabric Quality</label><div class="select-action"><select name="fabric_quality_id" id="fabricQuality" required><option value="">Choose quality</option>@foreach($qualities as $q)<option value="{{ $q->id }}" data-picks="{{ $q->picks }}" data-warp="{{ $q->warp_count }}" data-weft="{{ $q->weft_count }}" data-width="{{ $q->width }}" @selected(old('fabric_quality_id')==$q->id)>{{ $q->name }} ({{ $q->picks ?? '?' }} picks)</option>@endforeach</select><button class="btn secondary small" type="button" id="showNewQuality">Create Quality</button></div><div class="hint">Picks come from the saved fabric quality and are fixed on the contract.</div></div>
<div class="field"><label>Contract Quantity (metres) <span id="qtyOptional">— optional for Fabric Rate</span></label><input name="target_metres" id="metres" type="number" min="0.01" step="0.01" value="{{ old('target_metres') }}"></div>
<div class="field for-job"><label>Conversion Rate per Pick (PKR)</label><input name="rate_per_pick" id="pickRate" type="number" min="0" step="0.0001" value="{{ old('rate_per_pick') }}"></div>
<div class="field for-rate" style="display:none"><label>Agreed Fabric Sale Rate / Metre (PKR)</label><input name="rate_per_metre" id="fabricRate" type="number" min="0" step="0.0001" value="{{ old('rate_per_metre') }}"></div>
</div>

<div class="quick-modal" id="newPartyPanel" hidden><div class="quick-modal-backdrop" data-close-modal></div><section class="quick-modal-card" role="dialog" aria-modal="true" aria-labelledby="partyModalTitle"><button class="quick-modal-close" type="button" data-close-modal aria-label="Close">×</button><div class="kicker">New Account Contact</div><h2 id="partyModalTitle">Create Customer / Party</h2><p class="tagline">Add the customer here and it will be selected on this contract.</p><div class="form-grid modal-fields"><div class="field span-2"><label for="newPartyName">Party Name</label><input id="newPartyName" maxlength="150" placeholder="Customer or supplier name"></div><div class="field"><label for="newPartyType">Account Type</label><select id="newPartyType"><option value="customer">Customer</option><option value="supplier">Supplier</option><option value="both">Both</option></select></div><div class="field"><label for="newPartyPhone">Phone (optional)</label><input id="newPartyPhone" maxlength="60"></div></div><div class="quick-modal-actions"><button class="btn" type="button" id="saveNewParty">Create Customer / Party</button></div><div id="newPartyError" class="hint" role="alert"></div></section></div>

<div class="quick-modal" id="newQualityPanel" hidden><div class="quick-modal-backdrop" data-close-modal></div><section class="quick-modal-card" role="dialog" aria-modal="true" aria-labelledby="qualityModalTitle"><button class="quick-modal-close" type="button" data-close-modal aria-label="Close">×</button><div class="kicker">New Fabric Specification</div><h2 id="qualityModalTitle">Create Fabric Quality</h2><p class="tagline">Save the quality here and it will be selected on this contract.</p><div class="form-grid modal-fields"><div class="field span-2"><label for="newQualityName">Quality Name</label><input id="newQualityName" maxlength="150" placeholder="e.g. Grey 60x60"></div><div class="field"><label for="newQualityPicks">Picks</label><input id="newQualityPicks" type="number" min="0.01" max="10000" step="0.01" placeholder="e.g. 60"></div><div class="field"><label for="newQualityWarp">Warp Count</label><input id="newQualityWarp" maxlength="80" placeholder="e.g. 30s"></div><div class="field"><label for="newQualityWeft">Weft Count</label><input id="newQualityWeft" maxlength="80" placeholder="e.g. 20s"></div><div class="field"><label for="newQualityWidth">Width</label><input id="newQualityWidth" maxlength="80" placeholder="e.g. 63 inches"></div></div><div class="quick-modal-actions"><button class="btn" type="button" id="saveNewQuality">Create Quality</button></div><div id="newQualityError" class="hint" role="alert"></div></section></div>

<section id="partaSection" class="parta-section">
  <h2>Parta — Yarn Requirement</h2>
  <div class="note">The values below reproduce the supplied <strong>Fabric Costing</strong> formula. A bag is always <strong>100 lbs</strong>, whether it contains 24, 40 or any other number of cones. Yarn rates are optional estimating inputs and do NOT post inventory cost or COGS.</div>
  <div class="form-grid" style="margin-top:16px">
    <div class="field"><label>Reed</label><input id="partaReed" name="parta_reed" type="number" min="0.01" step="0.01" placeholder="e.g. 90" value="{{ old('parta_reed') }}"></div>
    <div class="field"><label>Picks (from fabric quality)</label><input id="partaPicks" type="number" readonly placeholder="Select fabric quality"></div>
    <div class="field"><label>Warp Count</label><input id="partaWarpCount" name="parta_warp_count" type="number" min="0.001" step="0.001" placeholder="e.g. 58.5" value="{{ old('parta_warp_count') }}"></div>
    <div class="field"><label>Weft Count</label><input id="partaWeftCount" name="parta_weft_count" type="number" min="0.001" step="0.001" placeholder="e.g. 58.5" value="{{ old('parta_weft_count') }}"></div>
    <div class="field"><label>Fabric Width (inches)</label><input id="partaWidth" name="parta_width" type="number" min="0.001" step="0.001" placeholder="e.g. 63.5" value="{{ old('parta_width') }}"></div>
    <div class="field"><label>Warp Yarn Rate (PKR / lb) — optional</label><input id="warpYarnRate" name="parta_warp_yarn_rate" type="number" min="0" step="0.0001" placeholder="e.g. 600" value="{{ old('parta_warp_yarn_rate') }}"></div>
    <div class="field"><label>Weft Yarn Rate (PKR / lb) — optional</label><input id="weftYarnRate" name="parta_weft_yarn_rate" type="number" min="0" step="0.0001" placeholder="e.g. 600" value="{{ old('parta_weft_yarn_rate') }}"></div>
  </div>
  <div id="partaPreview" class="parta-preview" aria-live="polite">Enter quantity, fabric quality, Reed, counts and width to preview required bags.</div>
  <div class="field" style="margin-top:15px"><label>Parta / Contract Remarks (optional)</label><textarea name="parta_details" placeholder="Any additional agreed terms or a calculation reference">{{ old('parta_details') }}</textarea></div>
  <div class="hint" style="margin-top:7px">The required bag amount is recalculated and verified on the server when the contract is saved. It cannot be manually overridden through this form.</div>
</section>
<div class="field" style="margin-top:17px"><label>Other Remarks (optional)</label><textarea name="notes">{{ old('notes') }}</textarea></div>
<div class="input-actions"><button class="btn">Save Contract</button></div>
</form></div>
@push('scripts')<script>
(() => {
  const type=document.getElementById('contractType');
  const quality=document.getElementById('fabricQuality');
  const defaults=@json($qualityDefaults);
  const ids={metres:'metres',reed:'partaReed',picks:'partaPicks',warp:'partaWarpCount',weft:'partaWeftCount',width:'partaWidth',conversion:'pickRate',warpRate:'warpYarnRate',weftRate:'weftYarnRate'};
  const fields=Object.fromEntries(Object.entries(ids).map(([k,v])=>[k,document.getElementById(v)]));
  const preview=document.getElementById('partaPreview');
  const trim4=n=>Math.trunc(n*10000+1e-8)/10000;
  const round2=n=>Math.round((n+Number.EPSILON)*100)/100;
  function numericDefault(raw) {
    if(raw===null||raw===undefined) return '';
    const text=String(raw).trim();
    return /^\d+(\.\d+)?\s*[sS]?$/.test(text)?text.replace(/[sS]/g,'').trim():'';
  }
  function selectQuality(force) {
    const selected=quality.selectedOptions[0];
    const d=defaults[quality.value]|| (selected?{picks:selected.dataset.picks,warp_count:selected.dataset.warp,weft_count:selected.dataset.weft,width:selected.dataset.width}:{});
    fields.picks.value=d.picks??'';
    for(const [key,name] of [['warp','warp_count'],['weft','weft_count'],['width','width']]) {
      if(force || !fields[key].value) fields[key].value=numericDefault(d[name]);
    }
    calculate();
  }
  function toggle() {
    const job=type.value==='job_work';
    document.querySelectorAll('.for-job').forEach(e=>e.style.display=job?'flex':'none');
    document.querySelectorAll('.for-rate').forEach(e=>e.style.display=job?'none':'flex');
    document.getElementById('partaSection').style.display=job?'block':'none';
    document.getElementById('fabricRate').required=!job;
    document.getElementById('qtyOptional').style.display=job?'none':'inline';
    [fields.metres,fields.reed,fields.warp,fields.weft,fields.width,fields.conversion].forEach(e=>e.required=job);
    calculate();
  }
  function calculate() {
    if(type.value!=='job_work') return;
    const q=Number(fields.metres.value),rd=Number(fields.reed.value),p=Number(fields.picks.value),
      w=Number(fields.warp.value),b=Number(fields.weft.value),wid=Number(fields.width.value),rate=Number(fields.conversion.value);
    if([q,rd,p,w,b,wid].some(x=>!(x>0))||fields.conversion.value===''||rate<0) {
      preview.textContent='Enter quantity, fabric quality, Reed, counts and width to preview required bags.';return;
    }
    const wl=trim4(rd*wid/w/20*1.0936/40),bl=trim4(p*wid/b/20*1.0936/40);
    const wb=round2(wl*q/100),bb=round2(bl*q/100);
    const conv=trim4(p*rate);
    const both=fields.warpRate.value!==''&&fields.weftRate.value!=='';
    const estimated=both?(trim4(wl*Number(fields.warpRate.value))+trim4(bl*Number(fields.weftRate.value))+conv):null;
    preview.innerHTML='<div class="parta-results">'
      +'<div><small>Warp bags</small><strong>'+wb.toFixed(2)+'</strong></div>'
      +'<div><small>Weft bags</small><strong>'+bb.toFixed(2)+'</strong></div>'
      +'<div><small>Total required bags</small><strong>'+(wb+bb).toFixed(2)+'</strong></div>'
      +'<div><small>Conversion / metre</small><strong>Rs '+conv.toFixed(4)+'</strong></div>'
      +(estimated===null?'':'<div><small>Estimated fabric cost / metre</small><strong>Rs '+estimated.toFixed(4)+'</strong></div>')
      +'</div>';
  }
  const partySelect=document.querySelector('select[name="party_id"]');
  const panel=document.getElementById('newPartyPanel');
  const qualityPanel=document.getElementById('newQualityPanel');
  // Keep dialogs outside the contract form/layout so fixed positioning is viewport-relative.
  document.body.append(panel,qualityPanel);
  function openModal(modal,focus){modal.hidden=false;document.body.classList.add('modal-open');document.getElementById(focus).focus();}
  function closeModal(modal){modal.hidden=true;if(document.querySelectorAll('.quick-modal:not([hidden])').length===0)document.body.classList.remove('modal-open');}
  document.querySelectorAll('[data-close-modal]').forEach(button=>button.addEventListener('click',()=>closeModal(button.closest('.quick-modal'))));
  document.addEventListener('keydown',event=>{if(event.key==='Escape'){document.querySelectorAll('.quick-modal:not([hidden])').forEach(closeModal);}});
  document.getElementById('showNewParty').addEventListener('click',()=>openModal(panel,'newPartyName'));
  document.getElementById('saveNewParty').addEventListener('click',async()=>{
    const button=document.getElementById('saveNewParty'),error=document.getElementById('newPartyError'),name=document.getElementById('newPartyName').value.trim();
    error.textContent=''; if(!name){error.textContent='Enter a party name.';return;}
    button.disabled=true;
    try {
      const response=await fetch(@json(route('parties.store')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({name,type:document.getElementById('newPartyType').value,phone:document.getElementById('newPartyPhone').value||null,return_to:'contract'})});
      const data=await response.json();
      if(!response.ok) throw new Error(data.message||Object.values(data.errors||{}).flat().join(' ')||'Could not add the party.');
      const party=data.party,option=new Option(party.name+' · '+party.type.charAt(0).toUpperCase()+party.type.slice(1),party.id,true,true);partySelect.add(option);closeModal(panel);document.getElementById('newPartyName').value='';
    } catch(e){error.textContent=e.message;} finally {button.disabled=false;}
  });
  document.getElementById('showNewQuality').addEventListener('click',()=>openModal(qualityPanel,'newQualityName'));
  document.getElementById('saveNewQuality').addEventListener('click',async()=>{
    const button=document.getElementById('saveNewQuality'),error=document.getElementById('newQualityError'),name=document.getElementById('newQualityName').value.trim();
    error.textContent='';if(!name){error.textContent='Enter a quality name.';return;}button.disabled=true;
    try {
      const response=await fetch(@json(route('qualities.store')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({name,picks:document.getElementById('newQualityPicks').value||null,warp_count:document.getElementById('newQualityWarp').value||null,weft_count:document.getElementById('newQualityWeft').value||null,width:document.getElementById('newQualityWidth').value||null})});
      const data=await response.json();if(!response.ok)throw new Error(data.message||Object.values(data.errors||{}).flat().join(' ')||'Could not add fabric quality.');
      const q=data.quality,label=q.name+' ('+(q.picks??'?')+' picks)',option=new Option(label,q.id,true,true);option.dataset.picks=q.picks??'';option.dataset.warp=q.warp_count??'';option.dataset.weft=q.weft_count??'';option.dataset.width=q.width??'';quality.add(option);defaults[q.id]={picks:q.picks,warp_count:q.warp_count,weft_count:q.weft_count,width:q.width};selectQuality(true);closeModal(qualityPanel);['newQualityName','newQualityPicks','newQualityWarp','newQualityWeft','newQualityWidth'].forEach(id=>document.getElementById(id).value='');
    }catch(e){error.textContent=e.message;}finally{button.disabled=false;}
  });
  quality.addEventListener('change',()=>selectQuality(true));type.addEventListener('change',toggle);
  Object.values(fields).forEach(e=>e.addEventListener('input',calculate));
  selectQuality(false);toggle();
})();
</script>@endpush
@endsection
