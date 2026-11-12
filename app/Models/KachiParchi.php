<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KachiParchi extends Model
{
    protected $guarded = [];
    public function factory() { return $this->belongsTo(Factory::class); }
    public function party() { return $this->belongsTo(Party::class); }
    public function contract() { return $this->belongsTo(Contract::class); }
    public function items() { return $this->hasMany(KachiItem::class); }
    public function pakki() { return $this->hasOne(PakkiParchi::class); }
    public function bill() { return $this->hasOne(Bill::class); }
}
