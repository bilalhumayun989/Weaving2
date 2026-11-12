<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $guarded = [];
    protected $casts = ['parta_snapshot' => 'array'];
    public function factory() { return $this->belongsTo(Factory::class); }
    public function party() { return $this->belongsTo(Party::class); }
    public function quality() { return $this->belongsTo(FabricQuality::class, 'fabric_quality_id'); }
    public function receipts() { return $this->hasMany(YarnReceipt::class); }
    public function yarnPurchases() { return $this->hasMany(YarnPurchase::class); }
    public function thaans() { return $this->hasMany(Thaan::class); }
}
