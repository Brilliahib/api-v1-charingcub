<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaycarePriceList extends BaseModel
{
    use HasFactory;

    public function daycare()
    {
        return $this->belongsTo(Daycare::class);
    }
}
