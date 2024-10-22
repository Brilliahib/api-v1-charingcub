<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityDaycareImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'daycare_id',
        'image_url',
    ];

    // Relasi dengan Daycare
    public function daycare()
    {
        return $this->belongsTo(Daycare::class);
    }
}
