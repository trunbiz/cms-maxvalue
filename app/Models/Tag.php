<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use \App\Models\Concerns\SelectExplicitColumns, HasFactory;

    protected $fillable = ['name', 'slug'];

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    public function series()
    {
        return $this->belongsToMany(Series::class, 'series_tag');
    }
}
