<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use \App\Models\Concerns\SelectExplicitColumns, HasFactory;

    protected $fillable = ['menu_id', 'parent_id', 'label', 'type', 'target_id', 'url', 'sort_order'];
}
