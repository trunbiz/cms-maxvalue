<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use \App\Models\Concerns\SelectExplicitColumns, HasFactory;

    protected $fillable = ['created_by', 'type', 'series_id', 'chapter_number', 'title', 'slug', 'slug_is_custom', 'excerpt', 'image', 'category_id', 'status', 'published_at', 'seo_title', 'seo_description', 'author_name', 'is_demo'];

    protected $casts = ['published_at' => 'datetime', 'slug_is_custom' => 'boolean'];

    public function category()
    {
        return $this->belongsTo(Category::class)->select(['id', 'name', 'slug']);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->without('role')->select(['id', 'name']);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->select(['categories.id', 'name', 'slug']);
    }

    public function series()
    {
        return $this->belongsTo(Series::class)->select(['id', 'title', 'slug', 'status']);
    }

    public function content()
    {
        return $this->hasOne(PostContent::class)->select(['id', 'post_id', 'content']);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->select(['tags.id', 'name', 'slug']);
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->where('published_at', '<=', now())->where(fn ($q) => $q->whereNull('series_id')->orWhereHas('series', fn ($s) => $s->select('series.id')->published()));
    }
}
