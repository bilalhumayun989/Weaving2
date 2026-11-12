@extends('print.layout')
@section('document')
<div class="meta"><strong>Party:</strong> {{ $party->name }}<br><span class="muted">Consolidated across all three factories, with factory shown per transaction.</span></div>
@foreach(['receivable'=>'Customer Receivable','payable'=>'Supplier Payable'] as $type=>$caption)
@php $data=$type==='receivable'?$receivable:$payable; @endphp
<h2>{{ $caption }} Running Account</h2><table><thead><tr><th>Date</th><th>Description</th><th>Factory</th><th class="right">Debit</th><th class="right">Credit</th><th class="right">Balance</th></tr></thead><tbody><tr><td>—</td><td>Opening balance</td><td>—</td><td>—</td><td>—</td><td class="right">{{ number_format($data['opening'],2) }}</td></tr>
@foreach($data['rows'] as $row)<tr><td>{{ $row['entry']->entry_on }}</td><td>{{ $row['entry']->description }}</td><td>{{ $row['entry']->factory?->name ?? 'Business' }}</td><td class="right">{{ number_format($row['entry']->debit,2) }}</td><td class="right">{{ number_format($row['entry']->credit,2) }}</td><td class="right">{{ number_format($row['balance'],2) }}</td></tr>@endforeach</tbody></table>
<div class="total">Closing {{ strtolower($caption) }}: Rs {{ number_format($data['closing'],2) }}</div>
@endforeach
@endsection
