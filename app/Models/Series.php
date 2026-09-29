<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    use \App\Models\Concerns\SelectExplicitColumns, HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'image', 'category_id', 'status', 'views', 'seo_title', 'seo_keywords', 'seo_description', 'is_demo'];

    protected $table = 'series';

    public function category()
    {
        return $this->belongsTo(Category::class)->select(['id', 'name', 'slug']);
    }

    public function chapters()
    {
        return $this->hasMany(Post::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'series_tag')->select(['tags.id', 'name', 'slug']);
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }
}
