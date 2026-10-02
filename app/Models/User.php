<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use \App\Models\Concerns\SelectExplicitColumns, HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'password', 'role_id'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['password' => 'hashed'];

    protected $with = ['role'];

    public function role()
    {
        return $this->belongsTo(Role::class)->select(['id', 'name', 'modules']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'Super Admin';
    }

    public function managesAllPosts(): bool
    {
        return $this->isSuperAdmin() || $this->role?->name === 'Admin';
    }

    public function hasModule(string $module): bool
    {
        return $this->isSuperAdmin() || in_array($module, $this->role?->modules ?? [], true);
    }
}
