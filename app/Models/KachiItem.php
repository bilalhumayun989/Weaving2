<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KachiItem extends Model
{
    protected $guarded = [];
    public function kachi() { return $this->belongsTo(KachiParchi::class, 'kachi_parchi_id'); }
    public function thaan() { return $this->belongsTo(Thaan::class); }
}
