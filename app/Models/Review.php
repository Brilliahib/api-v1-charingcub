<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends BaseModel
{
    use HasFactory;
    
    public function daycare()
    {
        return $this->belongsTo(Daycare::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
