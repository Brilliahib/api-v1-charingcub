<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Daycare extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

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
}
