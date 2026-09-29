<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use \App\Models\Concerns\SelectExplicitColumns, HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'seo_title', 'seo_keywords', 'seo_description'];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function series()
    {
        return $this->hasMany(Series::class);
    }
}
