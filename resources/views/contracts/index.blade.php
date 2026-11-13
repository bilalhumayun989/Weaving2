@extends('layouts.app')
@section('title','Contracts')
@section('content')

<div class="page-heading">
  <div>
    <div class="kicker">Commercial Agreements</div>
    <h1>Contracts</h1>
    <p class="tagline">Job Work and Fabric Rate contracts across all factories.</p>
  </div>
  <a class="btn" href="{{ route('contracts.create') }}">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:7px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Contract
  </a>
</div>

<div class="card" style="padding:0;overflow:hidden">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Reference</th>
          <th>Type</th>
          <th>Party / Buyer</th>
          <th>Factory</th>
          <th>Quality</th>
          <th>Yarn Supplier</th>
          <th class="num">Req. Bags</th>
          <th class="num">Bags Received</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($contracts as $c)
        <tr class="table-row-link" onclick="window.location='{{ route('contracts.show',$c) }}'">
          <td>
            <a href="{{ route('contracts.show',$c) }}" class="ref-link">{{ $c->reference }}</a>
          </td>
          <td>
            <span class="pill {{ $c->type === 'job_work' ? '' : 'dark' }}">
              {{ $c->type === 'job_work' ? 'Job Work' : 'Fabric Rate' }}
            </span>
          </td>
          <td>{{ $c->party->name }}</td>
          <td>
            @if(($c->factory_scope ?? 'single') === 'all')
              <span class="pill dark">All Factories</span>
            @else
              {{ $c->factory->name }}
            @endif
          </td>
          <td>{{ $c->quality->name }}</td>
          <td>{{ $c->type === 'job_work' ? ucfirst($c->yarn_supplier) : 'Factory' }}</td>
          <td class="num">{{ $c->type === 'job_work' ? number_format($c->required_bags, 2) : '—' }}</td>
          <td class="num">{{ $c->type === 'job_work' && $c->yarn_supplier === 'customer' ? number_format($c->receipts_sum_bags ?? 0, 2) : '—' }}</td>
          <td>
            <span class="status-pill status-{{ $c->status }}">{{ ucfirst($c->status) }}</span>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" class="empty">
            No contracts yet.
            <a href="{{ route('contracts.create') }}">Create the first one.</a>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($contracts->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--line)">
    {{ $contracts->links() }}
  </div>
  @endif
</div>

@endsection
