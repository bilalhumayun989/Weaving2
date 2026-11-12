<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PakkiParchi extends Model
{
    protected $guarded = [];
    public function kachi() { return $this->belongsTo(KachiParchi::class, 'kachi_parchi_id'); }
}
