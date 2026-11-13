@extends('print.layout')
@section('document')

{{-- ── Meta header ── --}}
<div class="meta cols">
  <div>
    <p><strong>Kachi Ref:</strong> {{ $kachi->reference }}</p>
    <p><strong>Factory:</strong> {{ $kachi->factory->name }}</p>
    <p><strong>Pakki Date:</strong> {{ $kachi->pakki->accepted_on }}</p>
  </div>
  <div>
    <p><strong>Party:</strong> {{ $kachi->party->name }}</p>
    <p><strong>Contract:</strong> {{ $kachi->contract?->reference ?? 'Own stock sale' }}</p>
    <p><strong>Bill:</strong> {{ $kachi->bill?->reference ?? '—' }}</p>
  </div>
</div>

{{-- ── Acceptance summary table ── --}}
<h2>Acceptance Summary</h2>
<table>
  <tbody>
    <tr>
      <td style="width:55%"><strong>Dispatched Metres</strong></td>
      <td class="right">{{ number_format($kachi->items->sum('metres'), 2) }} m</td>
    </tr>
    <tr>
      <td><strong>Accepted Metres</strong></td>
      <td class="right" style="color:#1a6e42;font-weight:700">{{ number_format($kachi->pakki->accepted_metres, 2) }} m</td>
    </tr>
    <tr>
      <td><strong>Rejected Metres</strong></td>
      <td class="right" style="color:#8e3030">{{ number_format($kachi->pakki->rejected_metres, 2) }} m</td>
    </tr>
    <tr>
      <td><strong>Accepted Thaan Count</strong></td>
      <td class="right">{{ $kachi->pakki->accepted_thaan_count }}</td>
    </tr>
    <tr>
      <td><strong>Rejected Thaan Count</strong></td>
      <td class="right">{{ $kachi->items->count() - $kachi->pakki->accepted_thaan_count }}</td>
    </tr>
  </tbody>
</table>

{{-- ── Billable total ── --}}
<div style="background:#eaf2f0;border:1px solid #c5dcd8;border-radius:7px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <span style="font-size:13px;color:#526870">Billable / Accepted Metres</span>
  <span style="font-size:20px;font-weight:800;color:#1b4444">{{ number_format($kachi->pakki->accepted_metres, 2) }} m</span>
</div>

<p class="muted" style="font-size:12px">Pakki records totals, not individual rejected Thaan IDs. Physical returns are reconciled using original markings.</p>

@endsection
