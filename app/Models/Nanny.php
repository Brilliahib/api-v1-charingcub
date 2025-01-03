<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nanny extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function daycare(){
        return $this->belongsTo(Daycare::class);
    }

    public function bookingNannies()
    {
        return $this->hasMany(BookingNannies::class);
    }
}
