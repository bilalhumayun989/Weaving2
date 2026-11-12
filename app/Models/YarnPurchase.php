<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YarnPurchase extends Model
{
    protected $guarded = [];
    public function factory() { return $this->belongsTo(Factory::class); }
    public function party() { return $this->belongsTo(Party::class); }
    public function contract() { return $this->belongsTo(Contract::class); }
}
