<nav class="actions report-tabs" aria-label="Report sections">
<a class="btn {{ request()->routeIs('reports.index')?'report-tab-active':'secondary' }}" href="{{ route('reports.index') }}">General Report</a>
<a class="btn {{ request()->routeIs('reports.sales')?'report-tab-active':'secondary' }}" href="{{ route('reports.sales') }}">Sales Report</a>
<a class="btn {{ request()->routeIs('reports.contracts')?'report-tab-active':'secondary' }}" href="{{ route('reports.contracts') }}">Contract Ledger</a>
</nav>
