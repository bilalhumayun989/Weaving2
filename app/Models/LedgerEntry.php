<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $guarded = [];
    public function party() { return $this->belongsTo(Party::class); }
    public function factory() { return $this->belongsTo(Factory::class); }
}
