<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingDaycare extends BaseModel
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function daycares()
    {
        return $this->belongsTo(Daycare::class, 'daycare_id');
    }

    public function priceList()
    {
        return $this->belongsTo(DaycarePriceList::class, 'daycare_price_list_id');
    }
}
