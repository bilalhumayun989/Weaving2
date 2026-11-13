@extends('layouts.app')
@section('title','Fabric Checking')
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Fabric Inventory</div>
    <h1>Fabric Checking &amp; Stock Entry</h1>
    <p class="tagline">Enter total metres for each factory. Each factory keeps its own stock balance.</p>
  </div>
  <a class="btn secondary" href="{{ route('fabric.stock') }}">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
    View Available Stock
  </a>
</div>

{{-- ── New Checking Report Form ── --}}
<div class="form-section-card" style="margin-bottom:24px">
  <div class="form-section-head">
    <div class="form-section-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="9 11 12 14 22 4"/>
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
      </svg>
    </div>
    <div>
      <div class="form-section-title">New Checking Report</div>
      <div class="form-section-sub">Record checked fabric metres by factory. A stock lot is generated automatically for each factory row.</div>
    </div>
  </div>

  <form method="POST" action="{{ route('fabric.checking.store') }}">
    @csrf
    <div class="form-grid">

      <div class="field">
        <label>Fabric Entry Type</label>
        <select id="entryType" name="entry_type" required>
          <option value="job_work">Job Work · Party Owned</option>
          <option value="own_stock">Own Fabric · For Stock</option>
          <option value="own_contract">Own Fabric · Against Contract</option>
        </select>
      </div>

      <div class="field">
        <label>Checking Date</label>
        <input name="checked_on" type="date" value="{{ date('Y-m-d') }}" required>
      </div>

      <div class="field">
        <label>Fabric Quality</label>
        <select name="fabric_quality_id" required>
          <option value="">Select quality</option>
          @foreach($qualities as $q)
            <option value="{{ $q->id }}">{{ $q->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="field" id="contractField">
        <label>Related Contract</label>
        <select name="contract_id" id="contractSelect">
          <option value="">Select contract</option>
          @foreach($contracts as $c)
            <option
              value="{{ $c->id }}"
              data-type="{{ $c->type }}"
              data-factory="{{ $c->factory_id }}"
              data-scope="{{ $c->factory_scope ?? 'single' }}"
            >{{ $c->reference }} · {{ $c->party->name }} · {{ ($c->factory_scope ?? 'single') === 'all' ? 'All Factories' : $c->factory->name }}</option>
          @endforeach
        </select>
        <div class="hint">Select a contract for Job Work or Fabric Rate. All-factory contracts accept totals from any factory.</div>
      </div>

    </div>

    {{-- ── Factory metres rows ── --}}
    <div class="fabric-factories-section">
      <div class="fabric-factories-header">
        <div class="fabric-factories-label">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          Metres by Factory
        </div>
        <button class="btn secondary small" id="addFactory" type="button">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Factory
        </button>
      </div>

      <div id="factoryRows">
        <div class="factory-row">
          <div class="field">
            <label>Factory</label>
            <select name="factory_rows[0][factory_id]" required>
              <option value="">Select factory</option>
              @foreach($factories as $f)
                <option value="{{ $f->id }}">{{ $f->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label>Total Metres from Factory</label>
            <input class="metres-input" name="factory_rows[0][metres]" type="number" min="0.01" step="0.01" required>
          </div>
          <div class="factory-row-action">
            <button type="button" class="btn secondary small remove-row" disabled>
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
        </div>
      </div>

      <div class="fabric-combined-total">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
        Combined total: <strong id="combinedTotal">0.00 m</strong>
        <span class="hint-inline">&nbsp;— a stock lot number is generated automatically for each factory.</span>
      </div>
    </div>

    <div class="field" style="margin-top:4px">
      <label>Notes <span class="hint-inline">(optional)</span></label>
      <input name="notes">
    </div>

    <div class="form-submit-bar">
      <button class="btn" type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:7px"><polyline points="20 6 9 17 4 12"/></svg>
        Save Checking Totals
      </button>
    </div>
  </form>
</div>

{{-- ── Recent Checking Reports ── --}}
<div class="card">
  <div class="card-header-row" style="margin-bottom:16px">
    <div>
      <h2>Recent Checking Reports</h2>
      <p class="muted small" style="margin:4px 0 0">All recorded fabric checking entries. Cancel before any lot from it has been dispatched.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Reference</th>
          <th>Date</th>
          <th>Factory Breakdown</th>
          <th class="num">Total Metres</th>
          <th>Type</th>
          <th>Quality</th>
          <th>Status / Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($checks as $r)
        <tr>
          <td><span class="ref-link">{{ $r->reference }}</span></td>
          <td>{{ $r->checked_on }}</td>
          <td>
            @foreach($r->thaans as $lot)
              <div class="fabric-factory-breakdown">
                <span class="fabric-factory-name">{{ $lot->factory->name }}</span>
                <span class="fabric-factory-metres">{{ number_format($lot->metres, 2) }} m</span>
              </div>
            @endforeach
          </td>
          <td class="num"><strong>{{ number_format($r->thaans->sum('metres'), 2) }}</strong></td>
          <td>
            <span class="pill {{ $r->entry_type === 'job_work' ? '' : 'dark' }}">
              {{ ucwords(str_replace('_', ' ', $r->entry_type)) }}
            </span>
          </td>
          <td>{{ $r->quality->name }}</td>
          <td>
            @if($r->voided_at)
              <span class="pill warn">Cancelled</span>
            @elseif(auth()->user()->role === 'admin')
              <form method="POST" action="{{ route('corrections.checking', $r) }}" onsubmit="return confirm('Remove this checking entry and its stock? This is allowed only before any stock lot from it has been dispatched.')">
                @csrf
                <div class="fabric-cancel-form">
                  <input name="reason" minlength="8" required placeholder="Reason (min. 8 chars)">
                  <button class="btn secondary small">Cancel Entry</button>
                </div>
              </form>
            @else
              <span class="pill">Recorded</span>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="empty">No checking reports yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($checks->hasPages())
  <div style="padding:16px 0 4px">{{ $checks->links() }}</div>
  @endif
</div>

@push('scripts')
<script>
const entryType     = document.getElementById('entryType');
const contractField = document.getElementById('contractField');
const contractSelect= document.getElementById('contractSelect');
const factoryRows   = document.getElementById('factoryRows');
const combinedTotal = document.getElementById('combinedTotal');

/* ── Template for a blank factory row ── */
function makeRow(index) {
  const div = document.createElement('div');
  div.className = 'factory-row';
  div.innerHTML = `
    <div class="field">
      <label>Factory</label>
      <select name="factory_rows[${index}][factory_id]" required>
        <option value="">Select factory</option>
        @foreach($factories as $f)
          <option value="{{ $f->id }}">{{ $f->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label>Total Metres from Factory</label>
      <input class="metres-input" name="factory_rows[${index}][metres]" type="number" min="0.01" step="0.01" required>
    </div>
    <div class="factory-row-action">
      <button type="button" class="btn secondary small remove-row">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>`;
  return div;
}

function renumber() {
  const rows = [...factoryRows.querySelectorAll('.factory-row')];
  rows.forEach((row, i) => {
    row.querySelector('select').name       = `factory_rows[${i}][factory_id]`;
    row.querySelector('.metres-input').name = `factory_rows[${i}][metres]`;
    row.querySelector('.remove-row').disabled = rows.length === 1;
  });
}

function updateTotal() {
  const sum = [...factoryRows.querySelectorAll('.metres-input')]
    .reduce((t, inp) => t + (Number(inp.value) || 0), 0);
  combinedTotal.textContent = sum.toFixed(2) + ' m';
}

function filterFactories() {
  const selected        = contractSelect.selectedOptions[0];
  const requiredFactory = selected && selected.dataset.scope !== 'all'
    ? selected.dataset.factory : null;
  factoryRows.querySelectorAll('select').forEach(sel => {
    [...sel.options].forEach(option => {
      option.hidden = !!requiredFactory && option.value !== '' && option.value !== requiredFactory;
      if (option.hidden && option.selected) sel.value = '';
    });
  });
}

function updateForm() {
  const stock = entryType.value === 'own_stock';
  contractField.style.display  = stock ? 'none' : 'flex';
  contractSelect.required       = !stock;
  if (stock) contractSelect.value = '';
  [...contractSelect.options].forEach(option => {
    option.hidden = !!option.dataset.type &&
      option.dataset.type !== (entryType.value === 'job_work' ? 'job_work' : 'fabric_rate');
  });
  if (contractSelect.selectedOptions[0]?.hidden) contractSelect.value = '';
  filterFactories();
}

document.getElementById('addFactory').addEventListener('click', () => {
  const count = factoryRows.querySelectorAll('.factory-row').length;
  factoryRows.append(makeRow(count));
  renumber();
  filterFactories();
});

factoryRows.addEventListener('click', event => {
  if (event.target.closest('.remove-row')) {
    event.target.closest('.factory-row').remove();
    renumber();
    updateTotal();
  }
});

factoryRows.addEventListener('input', updateTotal);
entryType.addEventListener('change', updateForm);
contractSelect.addEventListener('change', filterFactories);
updateForm();
renumber();
</script>
@endpush
@endsection
