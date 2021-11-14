<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Discussion extends Model
{
    protected $guarded = [];

    public function messages() {
        return $this->hasMany(Message::class);
    }
}
