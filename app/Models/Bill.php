<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $guarded = [];
    public function kachi() { return $this->belongsTo(KachiParchi::class, 'kachi_parchi_id'); }
    public function factory() { return $this->belongsTo(Factory::class); }
    public function party() { return $this->belongsTo(Party::class); }
    public function contract() { return $this->belongsTo(Contract::class); }
}
