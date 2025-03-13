<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nanny extends BaseModel
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function daycare()
    {
        return $this->belongsTo(Daycare::class);
    }

    public function bookingNannies()
    {
        return $this->hasMany(BookingNannies::class);
    }

    public function priceLists()
    {
        return $this->hasMany(NannyPriceList::class);
    }
}
