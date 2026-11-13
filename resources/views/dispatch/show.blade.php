@extends('layouts.app')
@section('title', 'Kachi · ' . $kachi->reference)
@section('content')

{{-- ── Page heading ── --}}
<div class="page-heading">
  <div>
    <div class="kicker">Kachi Parchi</div>
    <h1>{{ $kachi->reference }}</h1>
    <p class="tagline">{{ $kachi->party->name }} &middot; {{ $kachi->factory->name }} &middot; {{ $kachi->dispatched_on }}</p>
  </div>
  <div class="actions">
    <a class="btn secondary" href="{{ route('print.kachi', $kachi) }}" target="_blank">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print Kachi
    </a>
    @if($kachi->pakki)
    <a class="btn secondary" href="{{ route('print.pakki', $kachi) }}" target="_blank">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print Pakki
    </a>
    @endif
    @if($kachi->bill)
    <a class="btn secondary" href="{{ route('print.bill', $kachi->bill) }}" target="_blank">Print Bill</a>
    @endif
    <a class="btn secondary" href="{{ route('dispatch.index') }}">All Parchis</a>
  </div>
</div>

{{-- ── Metric cards ── --}}
<div class="grid grid-3" style="margin-bottom:24px">

  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--teal">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Dispatched Metres</div>
      <div class="dmc-value">{{ number_format($kachi->items->sum('metres'), 2) }}</div>
      <div class="dmc-sub">{{ $kachi->items->count() }} Thaans dispatched</div>
    </div>
  </div>

  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--green">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">Pakki Accepted</div>
      <div class="dmc-value">{{ $kachi->pakki ? number_format($kachi->pakki->accepted_metres, 2) : '—' }}</div>
      <div class="dmc-sub">Billable metres</div>
    </div>
  </div>

  <div class="dash-metric-card">
    <div class="dmc-icon dmc-icon--brand">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
    </div>
    <div class="dmc-body">
      <div class="dmc-label">{{ $kachi->bill?->billing_status === 'provisional' ? 'Provisional Bill' : 'Final Bill' }}</div>
      <div class="dmc-value">{{ $kachi->bill ? 'Rs '.number_format($kachi->bill->total, 2) : 'Pending' }}</div>
      <div class="dmc-sub">
        @if($kachi->bill)
          <span class="ref-link">{{ $kachi->bill->reference }}</span>
        @else
          Awaiting Pakki
        @endif
      </div>
    </div>
  </div>

</div>

{{-- ── Dispatched Thaans table ── --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header-row" style="margin-bottom:16px">
    <h2>Dispatched Thaans</h2>
    <span class="status-pill status-{{ $kachi->voided_at ? 'void' : str_replace('_','-',$kachi->status) }}">
      {{ $kachi->voided_at ? 'Cancelled' : ucwords(str_replace('_', ' ', $kachi->status)) }}
    </span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Thaan No.</th>
          <th class="num">Original Length (m)</th>
          <th>State</th>
        </tr>
      </thead>
      <tbody>
        @foreach($kachi->items as $i)
        <tr>
          <td class="muted small">{{ $loop->iteration }}</td>
          <td><span class="ref-link">{{ $i->thaan->thaan_no }}</span></td>
          <td class="num">{{ number_format($i->metres, 2) }}</td>
          <td>
            <span class="status-pill status-{{ $i->state === 'accepted' ? 'active' : ($i->state === 'returned' ? 'closed' : 'provisional') }}">
              {{ ucfirst($i->state) }}
            </span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- ── Cancelled notice ── --}}
@if($kachi->voided_at)
<div class="notice error" style="margin-bottom:24px">
  <strong>Cancelled Kachi.</strong> Reason: {{ $kachi->void_reason }}. It cannot receive a Pakki.
</div>

{{-- ── Record Pakki ── --}}
@elseif(!$kachi->pakki)
<div class="form-section-card" style="margin-bottom:24px">
  <div class="form-section-head">
    <div class="form-section-icon dmc-icon--green" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <div>
      <div class="form-section-title">Record Pakki Parchi</div>
      <div class="form-section-sub">Enter accepted and rejected totals to finalize the bill.</div>
    </div>
  </div>
  <div class="note" style="margin-bottom:20px">A provisional bill and receivable are already posted from Kachi dispatch metres. Pakki updates that same bill and ledger entry to the final accepted amount. Complete rejected Thaans are identified separately when returned.</div>
  <form method="POST" action="{{ route('dispatch.pakki.store', $kachi) }}">
    @csrf
    <div class="form-grid">
      <div class="field">
        <label>Pakki Date</label>
        <input type="date" name="accepted_on" value="{{ date('Y-m-d') }}" required>
      </div>
      <div class="field">
        <label>Accepted Metres</label>
        <input name="accepted_metres" type="number" min="0" step="0.01" required>
      </div>
      <div class="field">
        <label>Rejected Metres</label>
        <input name="rejected_metres" type="number" min="0" step="0.01" required>
      </div>
      <div class="field">
        <label>Accepted Thaan Count</label>
        <input name="accepted_thaan_count" type="number" min="0" step="1" required>
      </div>
    </div>
    <div class="form-submit-bar">
      <button class="btn" type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:7px"><polyline points="20 6 9 17 4 12"/></svg>
        Finalize Pakki and Bill
      </button>
    </div>
  </form>
</div>

{{-- ── Record returns ── --}}
@elseif($kachi->status === 'pending_returns')
<div class="form-section-card" style="margin-bottom:24px">
  <div class="form-section-head">
    <div class="form-section-icon dmc-icon--amber" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-5.1"/></svg>
    </div>
    <div>
      <div class="form-section-title">Record Physically Returned Thaans</div>
      <div class="form-section-sub">Pakki has been recorded and billed. Identify the actual returned Thaans.</div>
    </div>
  </div>
  <div class="note" style="margin-bottom:16px">When the customer returns rejected Thaans, identify the original marked Thaans. Their lengths are not remeasured. The selected count and total length must exactly match Pakki rejected totals.</div>
  <div class="dispatch-returns-summary">
    <div class="drs-item">
      <div class="drs-label">Rejected metres</div>
      <div class="drs-value">{{ number_format($kachi->pakki->rejected_metres, 2) }} m</div>
    </div>
    <div class="drs-item">
      <div class="drs-label">Rejected Thaans</div>
      <div class="drs-value">{{ $kachi->items->count() - $kachi->pakki->accepted_thaan_count }}</div>
    </div>
  </div>
  <form method="POST" action="{{ route('dispatch.returns.store', $kachi) }}">
    @csrf
    <div class="thaan-selector-section" style="margin:18px 0">
      <div class="thaan-selector-header">
        <div class="thaan-selector-label">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
          Select Returned Thaans
        </div>
      </div>
      <div class="thaan-selector-list">
        @foreach($kachi->items as $item)
        <label class="thaan-item">
          <input type="checkbox" name="thaan_ids[]" value="{{ $item->thaan_id }}">
          <span class="thaan-item-body">
            <span class="thaan-item-no">{{ $item->thaan->thaan_no }}</span>
          </span>
          <span class="thaan-item-metres">{{ number_format($item->metres, 2) }} m</span>
        </label>
        @endforeach
      </div>
    </div>
    <div class="form-submit-bar">
      <button class="btn" type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:7px"><polyline points="20 6 9 17 4 12"/></svg>
        Confirm Returned Thaans
      </button>
    </div>
  </form>
</div>

{{-- ── Settled ── --}}
@else
<div class="card" style="margin-bottom:24px">
  <div style="display:flex;align-items:center;gap:14px;padding:6px 0">
    <div class="dmc-icon dmc-icon--green" style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div>
      <div style="font-size:14px;font-weight:700;color:var(--ink)">Fully Settled</div>
      <p class="muted small" style="margin:3px 0 0">All fabric acceptance and physical returns for this Kachi are reconciled.</p>
    </div>
    <span class="status-pill status-active" style="margin-left:auto">Settled</span>
  </div>
</div>
@endif

{{-- ── Cancel Kachi (admin only) ── --}}
@if(!$kachi->pakki && !$kachi->voided_at && auth()->user()->role === 'admin')
<div class="card">
  <div class="card-header-row" style="margin-bottom:12px">
    <div>
      <h2>Cancel Unaccepted Kachi</h2>
      <p class="muted small" style="margin:4px 0 0">Only an administrator may cancel a Kachi before Pakki. All dispatched Thaans return to available stock.</p>
    </div>
  </div>
  <form method="POST" action="{{ route('corrections.kachi', $kachi) }}" onsubmit="return confirm('Cancel this Kachi and restore fabric to stock?')">
    @csrf
    <div class="field" style="max-width:500px">
      <label>Reason for cancellation</label>
      <input name="reason" required minlength="8" maxlength="1000" placeholder="Explain why this Kachi is being cancelled...">
    </div>
    <div class="input-actions">
      <button class="btn secondary">Cancel Kachi</button>
    </div>
  </form>
</div>
@endif

@endsection
