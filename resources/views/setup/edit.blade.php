@extends('layouts.app')
@section('title','Edit Master Record')
@section('content')
<div class="page-heading"><div><div class="kicker">Master Data</div><h1>Edit {{ ucfirst($kind) }}</h1><p class="tagline">Changes are recorded in the audit log.</p></div><a class="btn secondary" href="{{ route('setup.index') }}">Back</a></div>
<div class="card" style="max-width:750px"><form method="POST" action="{{ route('setup.update',[$kind,$record->id]) }}">@csrf @method('PUT')<div class="form-grid">
<div class="field span-2"><label>Name</label><input name="name" value="{{ old('name',$record->name) }}" required></div>
@if($kind==='party')
<div class="field"><label>Account Type</label><select name="type">@foreach(['customer','supplier','both'] as $type)<option value="{{ $type }}" @selected(old('type',$record->type)===$type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
<div class="field"><label>Phone</label><input name="phone" value="{{ old('phone',$record->phone) }}"></div>
<div class="field span-2"><label>Address</label><textarea name="address">{{ old('address',$record->address) }}</textarea></div>
<div class="field span-2"><div class="note">Opening balances cannot be changed here after setup. Use a documented, authorized accounting adjustment process for corrections.</div></div>
@elseif($kind==='quality')
<div class="field"><label>Picks</label><input name="picks" type="number" min="0.01" step="0.01" value="{{ old('picks',$record->picks) }}"></div>
<div class="field"><label>Warp Count</label><input name="warp_count" value="{{ old('warp_count',$record->warp_count) }}"></div>
<div class="field"><label>Weft Count</label><input name="weft_count" value="{{ old('weft_count',$record->weft_count) }}"></div>
<div class="field"><label>Width</label><input name="width" value="{{ old('width',$record->width) }}"></div>
<div class="field span-2"><label>Notes</label><textarea name="notes">{{ old('notes',$record->notes) }}</textarea></div>
<div class="field span-2"><div class="note">Existing Job Work contracts keep their original agreed pick count for billing; master edits apply to newly created contracts.</div></div>
@endif
</div><div class="input-actions"><button class="btn">Save Changes</button></div></form></div>
@endsection
