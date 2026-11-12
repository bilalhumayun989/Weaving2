<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factory extends Model
{
    protected $guarded = [];
    public function contracts() { return $this->hasMany(Contract::class); }
    public function checkingReports() { return $this->hasMany(CheckingReport::class); }
    public function thaans() { return $this->hasMany(Thaan::class); }
}
