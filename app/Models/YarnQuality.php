<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YarnQuality extends Model
{
    protected $guarded = [];

    public function purchases()
    {
        return $this->hasMany(YarnPurchase::class);
    }
}
