<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TalkAnswer extends BaseModel
{
    use HasFactory;

    public function talk()
    {
        return $this->belongsTo(Talk::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
