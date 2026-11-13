@extends('print.layout')
@section('document')

{{-- ── Meta header ── --}}
<div class="meta cols">
  <div>
    <p><strong>Kachi Ref:</strong> {{ $kachi->reference }}</p>
    <p><strong>Factory:</strong> {{ $kachi->factory->name }}</p>
    <p><strong>Dispatch Date:</strong> {{ $kachi->dispatched_on }}</p>
    <p><strong>Type:</strong> {{ $kachi->kind === 'job_work' ? 'Job Work' : 'Fabric Sale' }}</p>
  </div>
  <div>
    <p><strong>Party:</strong> {{ $kachi->party->name }}</p>
    <p><strong>Contract:</strong> {{ $kachi->contract?->reference ?? 'Own stock sale' }}</p>
    <p><strong>Quality:</strong> {{ $kachi->items->first()?->thaan?->quality?->name ?? '—' }}</p>
  </div>
</div>

{{-- ── Cancelled banner ── --}}
@if($kachi->voided_at)
<div style="background:#fff0f0;border:2px solid #f1c8c8;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#8e3030;font-weight:700;font-size:14px;text-align:center;letter-spacing:.04em">
  ⚠ CANCELLED — DO NOT USE
</div>
@endif

{{-- ── Thaan list ── --}}
<h2>Dispatched Thaans</h2>
<table>
  <thead>
    <tr>
      <th style="width:50px">#</th>
      <th>Thaan No.</th>
      <th class="right">Measured Length (m)</th>
    </tr>
  </thead>
  <tbody>
    @foreach($kachi->items as $i => $item)
    <tr>
      <td>{{ $i + 1 }}</td>
      <td>{{ $item->thaan->thaan_no }}</td>
      <td class="right">{{ number_format($item->metres, 2) }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

{{-- ── Dispatch total ── --}}
<div style="background:#eaf2f0;border:1px solid #c5dcd8;border-radius:7px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <span style="font-size:13px;color:#526870">
    {{ $kachi->items->count() }} Thaan{{ $kachi->items->count() !== 1 ? 's' : '' }} dispatched
  </span>
  <span style="font-size:20px;font-weight:800;color:#1b4444">
    {{ number_format($kachi->items->sum('metres'), 2) }} m
  </span>
</div>

<p class="muted" style="font-size:12px">Dispatch document only. Accepted metres are determined by the related Pakki Parchi.</p>

@endsection
