<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacilityDaycareImage extends BaseModel
{
    use HasFactory;

    // Relasi dengan Daycare
    public function daycare()
    {
        return $this->belongsTo(Daycare::class);
    }
}
