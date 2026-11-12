@extends('layouts.app')
@section('title','New Contract')
@section('content')
<div class="page-heading"><div><div class="kicker">Contracts</div><h1>Create a Contract</h1><p class="tagline">Job Work now calculates required bags directly from the approved Fabric Costing worksheet.</p></div><a class="btn secondary" href="{{ route('contracts.index') }}">Back to Contracts</a></div>
<div class="card"><form action="{{ route('contracts.store') }}" method="POST">@csrf
<div class="form-grid">
<div class="field"><label>Contract Type</label><select name="type" id="contractType" required><option value="job_work" @selected(old('type','job_work')==='job_work')>Job Work — party supplies yarn</option><option value="fabric_rate" @selected(old('type')==='fabric_rate')>Fabric Rate — factory supplies yarn</option></select></div>
<div class="field"><label>Contract Date</label><input name="contract_date" type="date" value="{{ old('contract_date',date('Y-m-d')) }}" required></div>
<div class="field"><label>Factory</label><select required name="factory_id"><option value="">Choose factory</option>@foreach($factories as $f)<option value="{{ $f->id }}" @selected(old('factory_id')==$f->id)>{{ $f->name }}</option>@endforeach</select></div>
<div class="field"><label>Party / Buyer</label><select required name="party_id"><option value="">Choose party</option>@foreach($parties as $p)<option value="{{ $p->id }}" @selected(old('party_id')==$p->id)>{{ $p->name }}</option>@endforeach</select></div>
<div class="field"><label>Fabric Quality</label><select name="fabric_quality_id" id="fabricQuality" required><option value="">Choose quality</option>@foreach($qualities as $q)<option value="{{ $q->id }}" @selected(old('fabric_quality_id')==$q->id)>{{ $q->name }} ({{ $q->picks ?? '?' }} picks)</option>@endforeach</select><div class="hint">Picks come from the saved fabric quality and are fixed on the contract.</div></div>
<div class="field"><label>Contract Quantity (metres) <span id="qtyOptional">— optional for Fabric Rate</span></label><input name="target_metres" id="metres" type="number" min="0.01" step="0.01" value="{{ old('target_metres') }}"></div>
<div class="field for-job"><label>Conversion Rate per Pick (PKR)</label><input name="rate_per_pick" id="pickRate" type="number" min="0" step="0.0001" value="{{ old('rate_per_pick') }}"></div>
<div class="field for-rate" style="display:none"><label>Agreed Fabric Sale Rate / Metre (PKR)</label><input name="rate_per_metre" id="fabricRate" type="number" min="0" step="0.0001" value="{{ old('rate_per_metre') }}"></div>
</div>

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
    const d=defaults[quality.value]||{};
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
  quality.addEventListener('change',()=>selectQuality(true));type.addEventListener('change',toggle);
  Object.values(fields).forEach(e=>e.addEventListener('input',calculate));
  selectQuality(false);toggle();
})();
</script>@endpush
@endsection
