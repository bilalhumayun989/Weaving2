@extends('layouts.app')
@section('title','Change Password')
@section('content')
<div class="page-heading"><div><div class="kicker">My Account</div><h1>Change Password</h1><p class="tagline">Use a strong, unique password.</p></div></div>
<div class="card" style="max-width:600px"><form method="POST" action="{{ route('security.password.update') }}">@csrf
<div class="field"><label>Current password</label><input name="current_password" type="password" required></div><br>
<div class="field"><label>New password (12+ characters, letters and numbers)</label><input name="password" type="password" minlength="12" required></div><br>
<div class="field"><label>Confirm new password</label><input name="password_confirmation" type="password" minlength="12" required></div>
<div class="input-actions"><button class="btn">Update Password</button></div></form></div>
@endsection
