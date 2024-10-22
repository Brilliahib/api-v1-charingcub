<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'daycare_id',
        'user_id',
        'rating',
        'comment',
    ];

    public function daycare()
    {
        return $this->belongsTo(Daycare::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
