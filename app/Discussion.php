<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Discussion extends Model
{
    protected $guarded = [];

    public function messages() {
        return $this->hasMany(Message::class);
    }
    public function creator() {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function destination() {
        return $this->belongsTo(User::class, 'destination_id');
    }
}
