<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Daycare extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'longitude' => 'float',
        'latitude' => 'float',
    ];    

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function nannies()
    {
        return $this->hasMany(Nanny::class);
    }

    public function facilityImages()
    {
        return $this->hasMany(FacilityDaycareImage::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function priceLists()
{
    return $this->hasMany(DaycarePriceList::class);
}
}
