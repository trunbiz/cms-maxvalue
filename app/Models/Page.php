<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use \App\Models\Concerns\SelectExplicitColumns, HasFactory;

    protected $fillable = ['title', 'slug', 'content', 'seo_title', 'seo_description', 'status'];

    protected $attributes = ['status' => 'draft'];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
