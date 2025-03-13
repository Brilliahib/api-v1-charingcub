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

    public function priceLists()
    {
        return $this->belongsTo(NannyPriceList::class, 'price_id');
    }
}
