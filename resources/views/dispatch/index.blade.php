@extends('layouts.app')
@section('title','Kachi / Pakki')
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Fabric Dispatch &amp; Acceptance</div>
    <h1>Kachi &amp; Pakki Parchi</h1>
    <p class="tagline">Kachi creates a provisional bill from dispatched metres; Pakki updates it to the final accepted amount.</p>
  </div>
</div>

{{-- ── Create Kachi Form ── --}}
<div class="form-section-card" style="margin-bottom:24px">
  <div class="form-section-head">
    <div class="form-section-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/>
        <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
      </svg>
    </div>
    <div>
      <div class="form-section-title">Create Kachi Parchi</div>
      <div class="form-section-sub">Select dispatched Thaans to generate a provisional bill for the customer.</div>
    </div>
  </div>

  <form method="POST" action="{{ route('dispatch.kachi.store') }}">
    @csrf
    <div class="form-grid">

      <div class="field">
        <label>Dispatch Type</label>
        <select name="kind" id="dispatchKind">
          <option value="job_work">Job Work / Conversion</option>
          <option value="fabric_sale">Factory-Owned Fabric Sale</option>
        </select>
      </div>

      <div class="field">
        <label>Dispatch Date</label>
        <input name="dispatched_on" type="date" value="{{ date('Y-m-d') }}" required>
      </div>

      <div class="field">
        <label>Factory</label>
        <select name="factory_id" required id="factory">
          <option value="">Select factory</option>
          @foreach($factories as $f)
            <option value="{{ $f->id }}">{{ $f->name }}</option>
          @endforeach
        </select>
        <div class="hint">A Kachi dispatch is recorded for one physical factory at a time.</div>
      </div>

      <div class="field">
        <label>Customer</label>
        <select name="party_id" required>
          <option value="">Select customer</option>
          @foreach($parties as $p)
            <option value="{{ $p->id }}">{{ $p->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="field span-2">
        <label>Contract</label>
        <select name="contract_id" id="contract">
          <option value="">No contract (own stock sale only)</option>
          @foreach($contracts as $c)
            <option
              value="{{ $c->id }}"
              data-type="{{ $c->type }}"
              data-party="{{ $c->party_id }}"
              data-factory="{{ $c->factory_id }}"
              data-scope="{{ $c->factory_scope ?? 'single' }}"
            >{{ $c->reference }} · {{ $c->party->name }}{{ ($c->factory_scope ?? 'single') === 'all' ? ' · All Factories' : '' }}</option>
          @endforeach
        </select>
        <div class="hint">Choose a factory to narrow contracts. Selecting a single-factory contract fills its factory automatically.</div>
      </div>

      <div class="field" id="rateField" style="display:none">
        <label>Sale Rate / Metre <span class="hint-inline">(for stock sale only)</span></label>
        <input type="number" name="rate_per_metre" min="0" step="0.0001" placeholder="Only if no contract">
      </div>

    </div>

    {{-- ── Thaan selector ── --}}
    <div class="thaan-selector-section">
      <div class="thaan-selector-header">
        <div class="thaan-selector-label">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
          Available Thaans for this Dispatch
        </div>
      </div>

      <div class="thaan-selector-list" id="thaanList">
        @forelse($thaans as $t)
          <label
            class="thaan-item"
            data-factory="{{ $t->factory_id }}"
            data-kind="{{ $t->ownership }}"
            data-contract="{{ $t->contract_id ?? '' }}"
            data-quality="{{ $t->fabric_quality_id }}"
            data-metres="{{ $t->metres }}"
          >
            <input type="checkbox" name="thaan_ids[]" value="{{ $t->id }}">
            <span class="thaan-item-body">
              <span class="thaan-item-no">{{ $t->thaan_no }}</span>
              <span class="thaan-item-meta">
                <span class="thaan-tag">{{ $t->quality->name }}</span>
                <span class="thaan-tag">{{ $t->factory->name }}</span>
                <span class="thaan-tag">{{ $t->contract_id ? 'Contract '.$t->contract_id : 'Own Stock' }}</span>
              </span>
            </span>
            <span class="thaan-item-metres">{{ number_format($t->metres, 2) }} m</span>
          </label>
        @empty
          <div class="empty" style="padding:24px">No available Thaans. Enter checked fabric first.</div>
        @endforelse
      </div>

      <div class="thaan-selector-footer">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
        <span id="dispatchSelectionSummary">0 Thaans selected</span>
        <span class="hint-inline">&nbsp;— select Thaans from one factory and one quality.</span>
      </div>
    </div>

    <div class="form-submit-bar">
      <button class="btn" type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:7px"><polyline points="20 6 9 17 4 12"/></svg>
        Create Kachi Parchi
      </button>
    </div>
  </form>
</div>

{{-- ── Kachi / Pakki Register ── --}}
<div class="card">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Kachi / Pakki Register</h2>
      <p class="muted small" style="margin:4px 0 0">All dispatches with their current acceptance and billing status.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Kachi Ref</th>
          <th>Date</th>
          <th>Party</th>
          <th>Factory</th>
          <th>Type</th>
          <th>Pakki</th>
          <th>Status</th>
          <th>Bill</th>
        </tr>
      </thead>
      <tbody>
        @forelse($kachis as $k)
        <tr class="table-row-link" onclick="window.location='{{ route('dispatch.show', $k) }}'">
          <td><a href="{{ route('dispatch.show', $k) }}" class="ref-link">{{ $k->reference }}</a></td>
          <td>{{ $k->dispatched_on }}</td>
          <td>{{ $k->party->name }}</td>
          <td>{{ $k->factory->name }}</td>
          <td>
            <span class="pill {{ $k->kind === 'job_work' ? '' : 'dark' }}">
              {{ $k->kind === 'job_work' ? 'Job Work' : 'Fabric Sale' }}
            </span>
          </td>
          <td>
            @if($k->voided_at)
              <span class="muted small">Cancelled</span>
            @elseif($k->pakki)
              <span class="pill">Recorded</span>
            @else
              <span class="pill warn">Awaiting</span>
            @endif
          </td>
          <td>
            <span class="status-pill status-{{ $k->voided_at ? 'void' : str_replace('_','-',$k->status) }}">
              {{ $k->voided_at ? 'Cancelled' : ucwords(str_replace('_', ' ', $k->status)) }}
            </span>
          </td>
          <td>
            @if($k->bill)
              <span class="ref-link">{{ $k->bill->reference }}</span>
            @else
              <span class="muted small">—</span>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="8" class="empty">No parchis yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($kachis->hasPages())
  <div style="padding:16px 0 4px">{{ $kachis->links() }}</div>
  @endif
</div>

@push('scripts')
<script>
const kind     = document.getElementById('dispatchKind');
const contract = document.getElementById('contract');
const factory  = document.getElementById('factory');
const party    = document.querySelector('select[name="party_id"]');
const rateField= document.getElementById('rateField');
const stockItems = [...document.querySelectorAll('.thaan-item')];

function filter() {
  const own = kind.value === 'fabric_sale';
  rateField.style.display = own ? 'flex' : 'none';
  contract.required = !own;
  [...contract.options].forEach(o => {
    if (!o.dataset.type) { o.hidden = false; return; }
    const typeMatches    = o.dataset.type === (own ? 'fabric_rate' : 'job_work');
    const factoryMatches = !factory.value || o.dataset.scope === 'all' || o.dataset.factory === factory.value;
    o.hidden = !typeMatches || !factoryMatches;
  });
  if (contract.selectedOptions[0]?.hidden) contract.value = '';
  stockItems.forEach(el => {
    const eligible =
      (!factory.value || el.dataset.factory === factory.value) &&
      el.dataset.kind === (own ? 'own' : 'job_work') &&
      (contract.value
        ? (el.dataset.contract === contract.value || (own && el.dataset.contract === ''))
        : own && el.dataset.contract === '');
    el.style.display = eligible ? 'flex' : 'none';
    if (!eligible) el.querySelector('input').checked = false;
  });
  updateSelection();
}

function updateSelection() {
  const selected = stockItems.filter(x => x.querySelector('input').checked);
  const total    = selected.reduce((s, x) => s + Number(x.dataset.metres || 0), 0);
  const qualities= new Set(selected.map(x => x.dataset.quality));
  const summary  = document.getElementById('dispatchSelectionSummary');
  if (summary) {
    let txt = selected.length + ' Thaan' + (selected.length !== 1 ? 's' : '') + ' selected · ' + total.toFixed(2) + ' m';
    if (qualities.size > 1) txt += ' · ⚠ Mixed qualities — select one quality only';
    summary.textContent = txt;
    summary.style.color = qualities.size > 1 ? '#9a3939' : '';
  }
}

contract.addEventListener('change', () => {
  const chosen = contract.selectedOptions[0];
  if (chosen?.dataset.factory && chosen.dataset.scope !== 'all') factory.value = chosen.dataset.factory;
  if (chosen?.dataset.party) party.value = chosen.dataset.party;
  filter();
});

[kind, factory].forEach(el => el.addEventListener('change', filter));
stockItems.forEach(x => x.querySelector('input').addEventListener('change', updateSelection));
filter();
</script>
@endpush
@endsection
