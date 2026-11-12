<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    protected $guarded = [];
    public function contracts() { return $this->hasMany(Contract::class); }
    public function ledgerEntries() { return $this->hasMany(LedgerEntry::class); }
    public function purchases() { return $this->hasMany(YarnPurchase::class); }
    public function bills() { return $this->hasMany(Bill::class); }
    public function payments() { return $this->hasMany(Payment::class); }
}
