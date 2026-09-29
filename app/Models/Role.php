<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Role extends Model
{
    use \App\Models\Concerns\SelectExplicitColumns, HasFactory;

    protected $fillable = ['name', 'modules'];

    protected $casts = ['modules' => 'array'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Role $role) {
            if ($role->name === 'Super Admin' || $role->users()->exists()) {
                throw ValidationException::withMessages(['role' => 'The Super Admin role and roles assigned to users cannot be deleted.']);
            }
        });
        static::updating(function (Role $role) {
            if ($role->getOriginal('name') === 'Super Admin' && $role->isDirty('name')) {
                throw ValidationException::withMessages(['name' => 'The Super Admin role cannot be renamed.']);
            }
        });
    }
}
