<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FabricQuality extends Model
{
    protected $guarded = [];
    protected $casts = ['parta_inputs'=>'array'];
    public function contracts() { return $this->hasMany(Contract::class); }
}
