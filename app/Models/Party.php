<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    protected $guarded = [];
    public function contracts() { return $this->hasMany(Contract::class); }
    public function ledgerEntries() { return $this->hasMany(LedgerEntry::class); }
}
