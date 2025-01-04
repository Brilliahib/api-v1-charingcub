<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends BaseModel
{
    use HasFactory;

    public function articleType()
    {
        return $this->belongsTo(ArticleType::class);
    }
}
