@extends('print.layout')
@section('document')

{{-- ── Party header ── --}}
<div class="meta">
  <strong style="font-size:14px">{{ $party->name }}</strong><br>
  <span class="muted">Consolidated across all three factories, with factory shown per transaction.</span>
</div>

@foreach(($party->type === 'customer'
  ? ['receivable' => 'Customer Receivable']
  : ($party->type === 'supplier'
    ? ['payable' => 'Supplier Payable']
    : ['receivable' => 'Customer Receivable', 'payable' => 'Supplier Payable'])
  ) as $type => $caption)

@php $data = $type === 'receivable' ? $receivable : $payable; @endphp

<h2>{{ $caption }} Running Account</h2>
<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>Description</th>
      <th>Factory</th>
      <th class="right">Debit</th>
      <th class="right">Credit</th>
      <th class="right">Balance</th>
    </tr>
  </thead>
  <tbody>
    <tr style="background:#f4f7f7">
      <td>—</td>
      <td><strong>Opening Balance</strong></td>
      <td>—</td>
      <td class="right">—</td>
      <td class="right">—</td>
      <td class="right"><strong>{{ number_format($data['opening'], 2) }}</strong></td>
    </tr>
    @foreach($data['rows'] as $row)
    <tr>
      <td>{{ $row['entry']->entry_on }}</td>
      <td>{{ $row['entry']->description }}</td>
      <td>{{ $row['entry']->factory?->name ?? 'Business' }}</td>
      <td class="right">{{ number_format($row['entry']->debit, 2) }}</td>
      <td class="right">{{ number_format($row['entry']->credit, 2) }}</td>
      <td class="right"><strong>{{ number_format($row['balance'], 2) }}</strong></td>
    </tr>
    @endforeach
  </tbody>
</table>

{{-- ── Closing balance block ── --}}
<div style="background:#eaf2f0;border:1px solid #c5dcd8;border-radius:7px;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <span style="font-size:12px;color:#526870">Closing {{ strtolower($caption) }}</span>
  <span style="font-size:18px;font-weight:800;color:#1b4444">Rs {{ number_format($data['closing'], 2) }}</span>
</div>

@endforeach

@endsection
