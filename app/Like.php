<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $guarded = [];

    public function owner() {
        return $this->belongsTo(User::class,'user_id');
    }

    public function entry() {
        return $this->belongsTo(Entry::class,'entry_id');
    }
}
