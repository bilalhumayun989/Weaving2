@extends('layouts.app')
@section('title','Users & Audit')
@section('content')
<div class="page-heading"><div><div class="kicker">Administration</div><h1>Access & Audit</h1><p class="tagline">Only administrators may create logins and reverse posted records.</p></div></div>
<div class="card"><h2>Create User</h2><form method="POST" action="{{ route('security.users.store') }}">@csrf<div class="form-grid">
<div class="field"><label>Name</label><input name="name" required></div><div class="field"><label>Email</label><input type="email" name="email" required></div>
<div class="field"><label>Role</label><select name="role"><option value="operator">Operator — records and payments</option><option value="admin">Administrator — corrections and users</option></select></div>
<div class="field"><label>Password (12+ characters, letters and numbers)</label><input type="password" name="password" minlength="12" required></div>
<div class="field"><label>Confirm Password</label><input type="password" name="password_confirmation" minlength="12" required></div>
</div><div class="input-actions"><button class="btn">Create User</button></div></form></div>
<div class="card"><h2>Active Users</h2><table><thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead><tbody>@foreach($users as $u)<tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ ucfirst($u->role) }}</td></tr>@endforeach</tbody></table></div>
<div class="card"><h2>Audit Activity</h2><div class="table-wrap"><table><thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Details</th></tr></thead><tbody>@forelse($logs as $l)<tr><td>{{ $l->created_at?->format('d M Y H:i') }}</td><td>{{ $l->user?->name ?? 'System' }}</td><td>{{ $l->action }}</td><td>{{ $l->record_type }} #{{ $l->record_id }}</td><td class="small">{{ json_encode($l->details,JSON_UNESCAPED_UNICODE) }}</td></tr>@empty<tr><td colspan="5">No audited changes yet.</td></tr>@endforelse</tbody></table></div>{{ $logs->links() }}</div>
@endsection
