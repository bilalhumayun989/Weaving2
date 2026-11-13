<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YarnReceipt extends Model
{
    protected $guarded = [];
    public function contract() { return $this->belongsTo(Contract::class); }
    public function factory() { return $this->belongsTo(Factory::class); }
    public function yarnQuality() { return $this->belongsTo(YarnQuality::class); }
}
