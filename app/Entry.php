<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    protected $guarded = [];

    public function comments() {
        return $this->hasMany(Comment::class, 'entry_id');
    }
    public function likes() {
        return $this->hasMany(Like::class, 'entry_id');
    }

    public function participants() {
        return $this->hasMany(Participation::class, 'entry_id');
    }

    public function medias() {
        return $this->hasMany(Media::class, 'entry_id');
    }

    public function owner() {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
