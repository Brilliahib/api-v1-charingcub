<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Daycare extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

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
}
