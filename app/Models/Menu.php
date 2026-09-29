<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use \App\Models\Concerns\SelectExplicitColumns, HasFactory;

    protected $fillable = ['name', 'slug'];

    public function items()
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order')->select(['id', 'menu_id', 'parent_id', 'label', 'type', 'target_id', 'url', 'sort_order']);
    }
}
