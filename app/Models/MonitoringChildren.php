<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringChildren extends BaseModel
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function daycare()
    {
        return $this->belongsTo(Daycare::class);
    }

    public function monitoringChat()
    {
        return $this->hasMany(MonitoringChat::class);
    }
}
