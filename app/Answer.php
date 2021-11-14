<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $guarded = [];

    public function answer_responses() {
        return $this->hasMany(AnswerResponse::class);
    }
}
