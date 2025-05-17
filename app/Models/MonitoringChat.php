<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringChat extends BaseModel
{
    use HasFactory;

    public function monitoringChildren()
    {
        return $this->belongsTo(MonitoringChildren::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
