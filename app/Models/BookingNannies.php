<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingNannies extends BaseModel
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function nannies()
    {
        return $this->belongsTo(Nanny::class, 'nanny_id');
    }
}
