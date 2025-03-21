<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleType extends BaseModel
{
    use HasFactory;

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
