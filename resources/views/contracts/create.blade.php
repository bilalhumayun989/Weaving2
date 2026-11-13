@extends('layouts.app')
@section('title','New Contract')
@section('content')

<div class="page-heading">
  <div>
    <div class="kicker">Contracts</div>
    <h1>Create a Contract</h1>
    <p class="tagline">Job Work calculates required bags directly from the Fabric Costing worksheet.</p>
  </div>
  <a class="btn secondary" href="{{ route('contracts.index') }}">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Contracts
  </a>
</div>

{{-- Error modal --}}
@if(session('form_error') || $errors->any())
<div class="quick-modal" id="contractErrorModal">
  <div class="quick-modal-backdrop" data-close-modal></div>
  <section class="quick-modal-card" role="dialog" aria-modal="true" aria-labelledby="contractErrorTitle">
    <button class="quick-modal-close" id="closeContractError" type="button" data-close-modal aria-label="Close">&times;</button>
    <div class="kicker">Please Review</div>
    <h2 id="contractErrorTitle">The contract wasn't saved</h2>
    <p>{{ session('form_error','Some contract details need correction. Your entries have been kept in the form below.') }}</p>
    @if($errors->any())
    <ul class="form-error-list">
      @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
    @endif
    <button class="btn" type="button" data-close-modal onclick="document.getElementById('contractForm').scrollIntoView({behavior:'smooth'});">Review and correct the form</button>
  </section>
</div>
@endif

<form id="contractForm" action="{{ route('contracts.store') }}" method="POST">
@csrf

{{-- ── Section 1: Core Details ── --}}
<div class="form-section-card">
  <div class="form-section-head">
    <div class="form-section-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
    </div>
    <div>
      <div class="form-section-title">Contract Details</div>
      <div class="form-section-sub">Type, party, factory and fabric quality</div>
    </div>
  </div>
  <div class="form-grid">
    <div class="field">
      <label>Contract Type</label>
      <select name="type" id="contractType" required>
        <option value="job_work" @selected(old('type','job_work')==='job_work')>Job Work — conversion contract</option>
        <option value="fabric_rate" @selected(old('type')==='fabric_rate')>Fabric Rate — factory-owned fabric</option>
      </select>
    </div>
    <div class="field">
      <label>Contract Date</label>
      <input name="contract_date" type="date" value="{{ old('contract_date', date('Y-m-d')) }}" required>
    </div>
    <div class="field">
      <label>Factory</label>
      <select required name="factory_id">
        <option value="">Choose factory</option>
        <option value="all" @selected(old('factory_id')==='all')>All Factories</option>
        @foreach($factories as $f)
          <option value="{{ $f->id }}" @selected(old('factory_id')==$f->id)>{{ $f->name }}</option>
        @endforeach
      </select>
      <div class="hint">All Factories makes this contract available to every factory in the business.</div>
    </div>
    <div class="field">
      <label>Party / Buyer</label>
      <div class="select-action">
        <select required name="party_id">
          <option value="">Choose party</option>
          @foreach($parties as $p)
            <option value="{{ $p->id }}" @selected(old('party_id')==$p->id)>{{ $p->name }} · {{ ucfirst($p->type) }}</option>
          @endforeach
        </select>
        <button class="btn secondary small" type="button" id="showNewParty">+ New Party</button>
      </div>
    </div>
    <div class="field">
      <label>Fabric Quality</label>
      <div class="select-action">
        <select name="fabric_quality_id" id="fabricQuality" required>
          <option value="">Choose quality</option>
          @foreach($qualities as $q)
            <option value="{{ $q->id }}" data-picks="{{ $q->picks }}" data-warp="{{ $q->warp_count }}" data-weft="{{ $q->weft_count }}" data-width="{{ $q->width }}" @selected(old('fabric_quality_id')==$q->id)>{{ $q->name }} ({{ $q->picks ?? '?' }} picks)</option>
          @endforeach
        </select>
        <button class="btn secondary small" type="button" id="showNewQuality">+ New Quality</button>
      </div>
      <div class="hint">Picks come from the saved fabric quality and are fixed on the contract.</div>
    </div>
    <div class="field">
      <label>Contract Quantity (metres) <span id="qtyOptional" class="hint-inline">— optional for Fabric Rate</span></label>
      <input name="target_metres" id="metres" type="number" min="0.01" step="0.01" value="{{ old('target_metres') }}">
    </div>
    <div class="field" id="yarnSupplierField">
      <label>Job Work Yarn Bags Supplied By</label>
      <select name="yarn_supplier" id="yarnSupplier" required>
        <option value="customer" @selected(old('yarn_supplier','customer')==='customer')>Customer / Party</option>
        <option value="factory" @selected(old('yarn_supplier')==='factory')>Factory</option>
      </select>
      <div class="hint">Decides whether customer bag receipts can be recorded against this contract.</div>
    </div>
    <div class="field for-job">
      <label>Conversion Rate per Pick (PKR)</label>
      <input name="rate_per_pick" id="pickRate" type="number" min="0" step="0.0001" value="{{ old('rate_per_pick') }}">
    </div>
    <div class="field for-rate" style="display:none">
      <label>Agreed Fabric Sale Rate / Metre (PKR)</label>
      <input name="rate_per_metre" id="fabricRate" type="number" min="0" step="0.0001" value="{{ old('rate_per_metre') }}">
    </div>
  </div>
</div>

{{-- ── Section 2: Parta ── --}}
<div class="form-section-card" id="partaSection">
  <div class="form-section-head">
    <div class="form-section-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    </div>
    <div>
      <div class="form-section-title">Parta — Yarn Requirement</div>
      <div class="form-section-sub">Fabric costing worksheet &amp; bag calculation</div>
    </div>
  </div>
  <div class="note" style="margin-bottom:18px">The values below reproduce the <strong>Fabric Costing</strong> formula. A bag is always <strong>100 lbs</strong>, regardless of cone count. For customer-supplied yarn, yarn rates are optional estimates; for factory-supplied yarn, both rates are required to calculate the customer bill per metre.</div>
  <div class="form-grid">
    <div class="field">
      <label>Reed</label>
      <input id="partaReed" name="parta_reed" type="number" min="0.01" step="0.01" placeholder="e.g. 90" value="{{ old('parta_reed') }}">
    </div>
    <div class="field">
      <label>Picks <span class="hint-inline">(from fabric quality)</span></label>
      <input id="partaPicks" type="number" readonly placeholder="Select fabric quality above">
    </div>
    <div class="field">
      <label>Warp Count</label>
      <input id="partaWarpCount" name="parta_warp_count" type="number" min="0.001" step="0.001" placeholder="e.g. 58.5" value="{{ old('parta_warp_count') }}">
    </div>
    <div class="field">
      <label>Weft Count</label>
      <input id="partaWeftCount" name="parta_weft_count" type="number" min="0.001" step="0.001" placeholder="e.g. 58.5" value="{{ old('parta_weft_count') }}">
    </div>
    <div class="field">
      <label>Fabric Width (inches)</label>
      <input id="partaWidth" name="parta_width" type="number" min="0.001" step="0.001" placeholder="e.g. 63.5" value="{{ old('parta_width') }}">
    </div>
    <div class="field">
      <label id="warpYarnRateLabel">Warp Yarn Rate (PKR / lb) — optional</label>
      <input id="warpYarnRate" name="parta_warp_yarn_rate" type="number" min="0" step="0.0001" placeholder="e.g. 600" value="{{ old('parta_warp_yarn_rate') }}">
    </div>
    <div class="field">
      <label id="weftYarnRateLabel">Weft Yarn Rate (PKR / lb) — optional</label>
      <input id="weftYarnRate" name="parta_weft_yarn_rate" type="number" min="0" step="0.0001" placeholder="e.g. 600" value="{{ old('parta_weft_yarn_rate') }}">
    </div>
  </div>
  <div id="partaPreview" class="parta-preview" aria-live="polite">Enter quantity, fabric quality, Reed, counts and width to preview required bags.</div>
  <div class="field" style="margin-top:18px">
    <label>Parta / Contract Remarks <span class="hint-inline">(optional)</span></label>
    <textarea name="parta_details" placeholder="Any additional agreed terms or a calculation reference">{{ old('parta_details') }}</textarea>
  </div>
  <div class="hint" style="margin-top:4px">The required bag amount is recalculated and verified on the server when the contract is saved. It cannot be manually overridden.</div>
</div>

{{-- ── Section 3: Print Details ── --}}
<div class="form-section-card">
  <div class="form-section-head">
    <div class="form-section-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    </div>
    <div>
      <div class="form-section-title">Print Details <span class="hint-inline" style="font-size:12px">(optional)</span></div>
      <div class="form-section-sub">Extra fields that appear on the printed contract — do not affect rates or Parta</div>
    </div>
  </div>
  <div class="form-grid">
    <div class="field"><label>Sales Tax Registration No.</label><input name="print_details[sales_tax_registration_no]" maxlength="100" value="{{ old('print_details.sales_tax_registration_no') }}"></div>
    <div class="field"><label>NTN</label><input name="print_details[ntn]" maxlength="100" value="{{ old('print_details.ntn') }}"></div>
    <div class="field"><label>Broker</label><input name="print_details[broker]" maxlength="150" value="{{ old('print_details.broker') }}"></div>
    <div class="field"><label>Grey Demand No.</label><input name="print_details[grey_demand_no]" maxlength="100" value="{{ old('print_details.grey_demand_no') }}"></div>
    <div class="field"><label>Weave Quality</label><input name="print_details[weave_quality]" maxlength="200" value="{{ old('print_details.weave_quality') }}"></div>
    <div class="field"><label>Invoice Quality</label><input name="print_details[invoice_quality]" maxlength="200" value="{{ old('print_details.invoice_quality') }}"></div>
    <div class="field"><label>Warp Component</label><input name="print_details[warp_component]" maxlength="255" value="{{ old('print_details.warp_component') }}"></div>
    <div class="field"><label>Weft Component</label><input name="print_details[weft_component]" maxlength="255" value="{{ old('print_details.weft_component') }}"></div>
    <div class="field span-2"><label>Specification</label><textarea name="print_details[specification]" maxlength="2000">{{ old('print_details.specification') }}</textarea></div>
    <div class="field span-2"><label>HS Code / Description</label><textarea name="print_details[hs_code_description]" maxlength="500">{{ old('print_details.hs_code_description') }}</textarea></div>
    <div class="field"><label>Last Date of Delivery</label><input type="date" name="print_details[last_delivery_date]" value="{{ old('print_details.last_delivery_date') }}"></div>
    <div class="field"><label>Broker Commission (%)</label><input type="number" min="0" max="100" step="0.01" name="print_details[broker_commission_percent]" value="{{ old('print_details.broker_commission_percent') }}"></div>
    <div class="field"><label>Planned Looms</label><input type="number" min="0" step="1" name="print_details[planned_looms]" value="{{ old('print_details.planned_looms') }}"></div>
    <div class="field"><label>Weight per Metre (kg)</label><input type="number" min="0" step="0.0001" name="print_details[weight_per_metre_kg]" value="{{ old('print_details.weight_per_metre_kg') }}"></div>
    <div class="field"><label>Selvedge</label><input name="print_details[selvedge]" maxlength="150" value="{{ old('print_details.selvedge') }}"></div>
    <div class="field"><label>Weave</label><input name="print_details[weave]" maxlength="150" value="{{ old('print_details.weave') }}"></div>
    <div class="field"><label>Count Margin</label><input name="print_details[count_margin]" maxlength="150" value="{{ old('print_details.count_margin') }}"></div>
    <div class="field"><label>Loom Type</label><input name="print_details[loom_type]" maxlength="150" value="{{ old('print_details.loom_type') }}"></div>
    <div class="field"><label>Stripe Size</label><input name="print_details[stripe_size]" maxlength="150" value="{{ old('print_details.stripe_size') }}"></div>
    <div class="field"><label>Process</label><input name="print_details[process]" maxlength="200" value="{{ old('print_details.process') }}"></div>
    <div class="field"><label>Payment Terms</label><input name="print_details[payment_terms]" maxlength="500" value="{{ old('print_details.payment_terms') }}"></div>
    <div class="field"><label>Packing</label><input name="print_details[packing]" maxlength="500" value="{{ old('print_details.packing') }}"></div>
    <div class="field span-2"><label>Special Remarks</label><textarea name="print_details[special_remarks]" maxlength="2500">{{ old('print_details.special_remarks') }}</textarea></div>
    <div class="field span-2"><label>Terms &amp; Conditions</label><textarea name="print_details[terms_conditions]" maxlength="5000">{{ old('print_details.terms_conditions') }}</textarea></div>
  </div>

  {{-- Delivery Schedule --}}
  <div class="field" style="margin-top:8px">
    <label>Delivery Schedule</label>
    <div class="table-wrap" style="margin-top:8px">
      <table>
        <thead>
          <tr><th>Date</th><th>Quantity</th><th>UOM</th><th>Location</th><th></th></tr>
        </thead>
        <tbody id="deliveryScheduleRows">
          @foreach(old('delivery_schedule', [[]]) as $index => $delivery)
          <tr data-delivery-row>
            <td><input type="date" name="delivery_schedule[{{ $index }}][date]" value="{{ $delivery['date'] ?? '' }}"></td>
            <td><input type="number" min="0.01" step="0.01" name="delivery_schedule[{{ $index }}][quantity]" value="{{ $delivery['quantity'] ?? '' }}"></td>
            <td><input name="delivery_schedule[{{ $index }}][uom]" maxlength="20" value="{{ $delivery['uom'] ?? 'M' }}"></td>
            <td><input name="delivery_schedule[{{ $index }}][location]" maxlength="150" value="{{ $delivery['location'] ?? '' }}"></td>
            <td><button type="button" class="btn secondary small remove-delivery-row" disabled>Remove</button></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <button type="button" class="btn secondary small" id="addDeliverySchedule" style="margin-top:10px">+ Add Delivery Date</button>
  </div>
</div>

{{-- ── Section 4: Remarks ── --}}
<div class="form-section-card">
  <div class="form-section-head">
    <div class="form-section-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </div>
    <div>
      <div class="form-section-title">Other Remarks <span class="hint-inline" style="font-size:12px">(optional)</span></div>
      <div class="form-section-sub">Internal notes, not printed on the contract</div>
    </div>
  </div>
  <div class="field">
    <textarea name="notes" placeholder="Any internal notes for this contract...">{{ old('notes') }}</textarea>
  </div>
</div>

<div class="form-submit-bar">
  <button class="btn" type="submit">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:7px"><polyline points="20 6 9 17 4 12"/></svg>
    Save Contract
  </button>
  <a href="{{ route('contracts.index') }}" class="btn secondary">Cancel</a>
</div>

</form>

{{-- New Party Modal --}}
<div class="quick-modal" id="newPartyPanel" hidden>
  <div class="quick-modal-backdrop" data-close-modal></div>
  <section class="quick-modal-card" role="dialog" aria-modal="true" aria-labelledby="partyModalTitle">
    <button class="quick-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
    <div class="kicker">New Customer</div>
    <h2 id="partyModalTitle">Create Customer</h2>
    <p class="tagline">Creates a customer-only account and selects it on this contract.</p>
    <div class="form-grid modal-fields">
      <div class="field span-2"><label for="newPartyName">Customer Name</label><input id="newPartyName" maxlength="150" placeholder="Customer name"></div>
      <div class="field span-2"><label for="newPartyPhone">Phone (optional)</label><input id="newPartyPhone" maxlength="60"></div>
    </div>
    <div class="quick-modal-actions"><button class="btn" type="button" id="saveNewParty">Create Customer</button></div>
    <div id="newPartyError" class="hint" role="alert"></div>
  </section>
</div>

{{-- New Quality Modal --}}
<div class="quick-modal" id="newQualityPanel" hidden>
  <div class="quick-modal-backdrop" data-close-modal></div>
  <section class="quick-modal-card" role="dialog" aria-modal="true" aria-labelledby="qualityModalTitle">
    <button class="quick-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
    <div class="kicker">New Fabric Specification</div>
    <h2 id="qualityModalTitle">Create Fabric Quality</h2>
    <p class="tagline">Save the quality here and it will be selected on this contract.</p>
    <div class="form-grid modal-fields">
      <div class="field span-2"><label for="newQualityName">Quality Name</label><input id="newQualityName" maxlength="150" placeholder="e.g. Grey 60x60"></div>
      <div class="field"><label for="newQualityPicks">Picks</label><input id="newQualityPicks" type="number" min="0.01" max="10000" step="0.01" placeholder="e.g. 60"></div>
      <div class="field"><label for="newQualityWarp">Warp Count</label><input id="newQualityWarp" maxlength="80" placeholder="e.g. 30s"></div>
      <div class="field"><label for="newQualityWeft">Weft Count</label><input id="newQualityWeft" maxlength="80" placeholder="e.g. 20s"></div>
      <div class="field"><label for="newQualityWidth">Width</label><input id="newQualityWidth" maxlength="80" placeholder="e.g. 63 inches"></div>
    </div>
    <div class="quick-modal-actions"><button class="btn" type="button" id="saveNewQuality">Create Quality</button></div>
    <div id="newQualityError" class="hint" role="alert"></div>
  </section>
</div>

@push('scripts')
<script>
(() => {
  const type=document.getElementById('contractType');
  const quality=document.getElementById('fabricQuality');
  const yarnSupplier=document.getElementById('yarnSupplier');
  const yarnSupplierField=document.getElementById('yarnSupplierField');
  const warpRateLabel=document.getElementById('warpYarnRateLabel');
  const weftRateLabel=document.getElementById('weftYarnRateLabel');
  const defaults=@json($qualityDefaults);
  const ids={metres:'metres',reed:'partaReed',picks:'partaPicks',warp:'partaWarpCount',weft:'partaWeftCount',width:'partaWidth',conversion:'pickRate',warpRate:'warpYarnRate',weftRate:'weftYarnRate'};
  const fields=Object.fromEntries(Object.entries(ids).map(([k,v])=>[k,document.getElementById(v)]));
  const preview=document.getElementById('partaPreview');
  const trim4=n=>Math.trunc(n*10000+1e-8)/10000;
  const round2=n=>Math.round((n+Number.EPSILON)*100)/100;
  function numericDefault(raw){if(raw===null||raw===undefined)return '';const text=String(raw).trim();return /^\d+(\.\d+)?\s*[sS]?$/.test(text)?text.replace(/[sS]/g,'').trim():'';}
  function selectQuality(force){const selected=quality.selectedOptions[0];const d=defaults[quality.value]||(selected?{picks:selected.dataset.picks,warp_count:selected.dataset.warp,weft_count:selected.dataset.weft,width:selected.dataset.width}:{});fields.picks.value=d.picks??'';for(const[key,name]of[['warp','warp_count'],['weft','weft_count'],['width','width']]){if(force||!fields[key].value)fields[key].value=numericDefault(d[name]);}calculate();}
  function toggle(){const job=type.value==='job_work';document.querySelectorAll('.for-job').forEach(e=>e.style.display=job?'flex':'none');document.querySelectorAll('.for-rate').forEach(e=>e.style.display=job?'none':'flex');document.getElementById('partaSection').style.display=job?'block':'none';document.getElementById('fabricRate').required=!job;document.getElementById('qtyOptional').style.display=job?'none':'inline';yarnSupplierField.style.display=job?'flex':'none';yarnSupplier.required=job;const factoryYarn=job&&yarnSupplier.value==='factory';fields.warpRate.required=factoryYarn;fields.weftRate.required=factoryYarn;warpRateLabel.textContent='Warp Yarn Rate (PKR / lb)'+(factoryYarn?' — required for billing':' — optional');weftRateLabel.textContent='Weft Yarn Rate (PKR / lb)'+(factoryYarn?' — required for billing':' — optional');[fields.metres,fields.reed,fields.warp,fields.weft,fields.width,fields.conversion].forEach(e=>e.required=job);calculate();}
  function calculate(){if(type.value!=='job_work')return;const q=Number(fields.metres.value),rd=Number(fields.reed.value),p=Number(fields.picks.value),w=Number(fields.warp.value),b=Number(fields.weft.value),wid=Number(fields.width.value),rate=Number(fields.conversion.value);if([q,rd,p,w,b,wid].some(x=>!(x>0))||fields.conversion.value===''||rate<0){preview.textContent='Enter quantity, fabric quality, Reed, counts and width to preview required bags.';return;}const wl=trim4(rd*wid/w/20*1.0936/40),bl=trim4(p*wid/b/20*1.0936/40);const wb=round2(wl*q/100),bb=round2(bl*q/100);const conv=trim4(p*rate);const both=fields.warpRate.value!==''&&fields.weftRate.value!=='';const estimated=both?(trim4(wl*Number(fields.warpRate.value))+trim4(bl*Number(fields.weftRate.value))+conv):null;preview.innerHTML='<div class="parta-results"><div><small>Warp bags</small><strong>'+wb.toFixed(2)+'</strong></div><div><small>Weft bags</small><strong>'+bb.toFixed(2)+'</strong></div><div><small>Total required bags</small><strong>'+(wb+bb).toFixed(2)+'</strong></div><div><small>Conversion / metre</small><strong>Rs '+conv.toFixed(4)+'</strong></div>'+(estimated===null?'':'<div><small>Estimated fabric cost / metre</small><strong>Rs '+estimated.toFixed(4)+'</strong></div>')+'</div>';}
  const partySelect=document.querySelector('select[name="party_id"]');
  const panel=document.getElementById('newPartyPanel');
  const qualityPanel=document.getElementById('newQualityPanel');
  const errorPanel=document.getElementById('contractErrorModal');
  document.body.append(panel,qualityPanel);
  if(errorPanel){document.body.append(errorPanel);document.body.classList.add('modal-open');document.getElementById('closeContractError').focus();}
  function openModal(modal,focus){modal.hidden=false;document.body.classList.add('modal-open');document.getElementById(focus).focus();}
  function closeModal(modal){modal.hidden=true;if(document.querySelectorAll('.quick-modal:not([hidden])').length===0)document.body.classList.remove('modal-open');}
  document.querySelectorAll('[data-close-modal]').forEach(button=>button.addEventListener('click',()=>closeModal(button.closest('.quick-modal'))));
  document.addEventListener('keydown',event=>{if(event.key==='Escape'){document.querySelectorAll('.quick-modal:not([hidden])').forEach(closeModal);}});
  document.getElementById('showNewParty').addEventListener('click',()=>openModal(panel,'newPartyName'));
  document.getElementById('saveNewParty').addEventListener('click',async()=>{const button=document.getElementById('saveNewParty'),error=document.getElementById('newPartyError'),name=document.getElementById('newPartyName').value.trim();error.textContent='';if(!name){error.textContent='Enter a party name.';return;}button.disabled=true;try{const response=await fetch(@json(route('parties.store')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({name,type:'customer',phone:document.getElementById('newPartyPhone').value||null,return_to:'contract'})});const data=await response.json();if(!response.ok)throw new Error(data.message||Object.values(data.errors||{}).flat().join(' ')||'Could not add the party.');const party=data.party,option=new Option(party.name+' · '+party.type.charAt(0).toUpperCase()+party.type.slice(1),party.id,true,true);partySelect.add(option);closeModal(panel);document.getElementById('newPartyName').value='';}catch(e){error.textContent=e.message;}finally{button.disabled=false;}});
  document.getElementById('showNewQuality').addEventListener('click',()=>openModal(qualityPanel,'newQualityName'));
  document.getElementById('saveNewQuality').addEventListener('click',async()=>{const button=document.getElementById('saveNewQuality'),error=document.getElementById('newQualityError'),name=document.getElementById('newQualityName').value.trim();error.textContent='';if(!name){error.textContent='Enter a quality name.';return;}button.disabled=true;try{const response=await fetch(@json(route('qualities.store')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({name,picks:document.getElementById('newQualityPicks').value||null,warp_count:document.getElementById('newQualityWarp').value||null,weft_count:document.getElementById('newQualityWeft').value||null,width:document.getElementById('newQualityWidth').value||null})});const data=await response.json();if(!response.ok)throw new Error(data.message||Object.values(data.errors||{}).flat().join(' ')||'Could not add fabric quality.');const q=data.quality,label=q.name+' ('+(q.picks??'?')+' picks)',option=new Option(label,q.id,true,true);option.dataset.picks=q.picks??'';option.dataset.warp=q.warp_count??'';option.dataset.weft=q.weft_count??'';option.dataset.width=q.width??'';quality.add(option);defaults[q.id]={picks:q.picks,warp_count:q.warp_count,weft_count:q.weft_count,width:q.width};selectQuality(true);closeModal(qualityPanel);['newQualityName','newQualityPicks','newQualityWarp','newQualityWeft','newQualityWidth'].forEach(id=>document.getElementById(id).value='');}catch(e){error.textContent=e.message;}finally{button.disabled=false;}});
  const scheduleRows=document.getElementById('deliveryScheduleRows');
  const renumberSchedule=()=>[...scheduleRows.querySelectorAll('[data-delivery-row]')].forEach((row,index,rows)=>{row.querySelectorAll('input').forEach(input=>input.name=input.name.replace(/delivery_schedule\[\d+\]/,`delivery_schedule[${index}]`));row.querySelector('.remove-delivery-row').disabled=rows.length===1;});
  document.getElementById('addDeliverySchedule').addEventListener('click',()=>{const row=scheduleRows.querySelector('[data-delivery-row]').cloneNode(true);row.querySelectorAll('input').forEach(input=>input.value=input.name.endsWith('[uom]')?'M':'');scheduleRows.append(row);renumberSchedule();});
  scheduleRows.addEventListener('click',event=>{if(!event.target.matches('.remove-delivery-row'))return;if(scheduleRows.querySelectorAll('[data-delivery-row]').length>1){event.target.closest('[data-delivery-row]').remove();renumberSchedule();}});
  renumberSchedule();
  quality.addEventListener('change',()=>selectQuality(true));
  type.addEventListener('change',toggle);
  yarnSupplier.addEventListener('change',toggle);
  Object.values(fields).forEach(e=>e.addEventListener('input',calculate));
  selectQuality(false);toggle();
})();
</script>
@endpush
@endsection
