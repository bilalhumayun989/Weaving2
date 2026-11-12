<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thaan extends Model
{
    protected $guarded = [];
    public function checkingReport() { return $this->belongsTo(CheckingReport::class); }
    public function factory() { return $this->belongsTo(Factory::class); }
    public function quality() { return $this->belongsTo(FabricQuality::class, 'fabric_quality_id'); }
    public function contract() { return $this->belongsTo(Contract::class); }
}
