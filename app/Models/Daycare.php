<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Daycare extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'images',
        'description',
        'opening_hours',
        'closing_hours',
        'opening_days',
        'phone_number',
        'rating',
        'reviewers_count',
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function facilityImages()
    {
        return $this->hasMany(FacilityDaycareImage::class);
    }
}
