<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class NannyPriceList extends BaseModel
{
    use HasFactory;

    public function nanny()
    {
        return $this->belongsTo(Nanny::class);
    }
}
