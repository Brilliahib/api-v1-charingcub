<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Talk extends BaseModel
{
    use HasFactory;

    public function talkAnswers()
    {
        return $this->hasMany(TalkAnswer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
