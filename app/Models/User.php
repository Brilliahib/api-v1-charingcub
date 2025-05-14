<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function daycare()
    {
        return $this->hasOne(Daycare::class);
    }

    public function nannies()
    {
        return $this->hasOne(Nanny::class);
    }

    public function bookingNannies()
    {
        return $this->hasMany(BookingNannies::class);
    }

    public function bookingDaycares()
    {
        return $this->hasMany(BookingDaycare::class);
    }

    public function talks()
    {
        return $this->hasMany(Talk::class);
    }

    public function talkAnswers()
    {
        return $this->hasMany(TalkAnswer::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function monitoringChildrens()
    {
        return $this->hasMany(MonitoringChildren::class);
    }

    public function monitoringChats()
    {
        return $this->hasMany(MonitoringChat::class);
    }
}
