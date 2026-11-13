@extends('print.layout')
@section('document')

@php
  $factoryYarnJobWork = $bill->type === 'conversion' && $bill->contract?->yarn_supplier === 'factory';
  $jobWorkRatePerMetre = $factoryYarnJobWork ? $bill->rate : (float)$bill->picks * (float)$bill->rate;
@endphp

{{-- ── Bill meta header ── --}}
<div class="meta cols">
  <div>
    <p>
      <strong>
        @if($bill->billing_status === 'provisional') Provisional Bill:
        @elseif($bill->billing_status === 'void') Voided Bill:
        @else Final Bill:
        @endif
      </strong>
      {{ $bill->reference }}
    </p>
    <p><strong>Invoice Date:</strong> {{ $bill->billed_on }}</p>
    <p><strong>Factory:</strong> {{ $bill->factory->name }}</p>
    <p><strong>Kachi Ref:</strong> {{ $bill->kachi->reference }}</p>
  </div>
  <div>
    <p><strong>Bill To:</strong> {{ $bill->party->name }}</p>
    <p><strong>Contract:</strong> {{ $bill->contract?->reference ?? 'Stock Sale' }}</p>
    @if($bill->contract?->quality)
    <p><strong>Fabric Quality:</strong> {{ $bill->contract->quality->name }}</p>
    @endif
  </div>
</div>

{{-- ── Voided banner ── --}}
@if($bill->billing_status === 'void')
<div style="background:#fff0f0;border:2px solid #f1c8c8;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#8e3030;font-weight:700;font-size:14px;text-align:center;letter-spacing:.04em">
  ⚠ VOIDED BILL — DO NOT USE
</div>
@endif

{{-- ── Line items ── --}}
<h2>Invoice Details</h2>
<table>
  <thead>
    <tr>
      <th>Description</th>
      <th class="right">{{ $bill->billing_status === 'final' ? 'Accepted Metres' : 'Dispatched Metres' }}</th>
      <th class="right">Rate / m (PKR)</th>
      <th class="right">Amount (PKR)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>
        @if($bill->type === 'conversion')
          {{ $factoryYarnJobWork ? 'Job Work — Factory-Supplied Yarn' : 'Job Work — Customer-Supplied Yarn' }}
        @else
          Factory-Owned Fabric Sale
        @endif
        @if($bill->contract)
          &middot; {{ $bill->contract->quality?->name }}
        @endif
      </td>
      <td class="right">{{ number_format($bill->accepted_metres, 2) }}</td>
      <td class="right">{{ number_format($bill->type === 'conversion' ? $jobWorkRatePerMetre : $bill->rate, 4) }}</td>
      <td class="right">{{ number_format($bill->total, 2) }}</td>
    </tr>
  </tbody>
</table>

{{-- ── Total block ── --}}
<div style="background:#eaf2f0;border:1px solid #c5dcd8;border-radius:7px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <span style="font-size:13px;color:#526870">
    {{ $bill->billing_status === 'provisional' ? 'Provisional Amount' : ($bill->billing_status === 'void' ? 'Voided Amount' : 'Final Amount') }}
  </span>
  <span style="font-size:20px;font-weight:800;color:#1b4444">Rs {{ number_format($bill->total, 2) }}</span>
</div>

{{-- ── Calculation notes ── --}}
<h2>Calculation Notes</h2>
<table>
  <tbody>
    <tr>
      <td style="width:45%"><strong>Metres</strong></td>
      <td>{{ number_format($bill->accepted_metres, 2) }} {{ $bill->billing_status === 'final' ? 'accepted' : 'dispatched' }} metres</td>
    </tr>
    <tr>
      <td><strong>Rate Applied</strong></td>
      <td>Rs {{ number_format($bill->type === 'conversion' ? $jobWorkRatePerMetre : $bill->rate, 4) }} per metre</td>
    </tr>
    @if($bill->type === 'conversion')
    <tr>
      <td><strong>Rate Basis</strong></td>
      <td>
        @if($factoryYarnJobWork)
          Includes contract yarn estimate and conversion charge.
        @else
          Agreed conversion charge only; yarn supplied by the customer.
        @endif
      </td>
    </tr>
    @endif
    <tr>
      <td><strong>Bill Status</strong></td>
      <td>
        @if($bill->billing_status === 'provisional')
          Provisional — final amount will be updated when Pakki is entered.
        @else
          Final — based on customer-accepted Pakki metres.
        @endif
        Taxes and COGS are not included in this scope.
      </td>
    </tr>
  </tbody>
</table>

@endsection
