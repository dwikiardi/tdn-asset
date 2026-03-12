<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
        'tridatu_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
    ];

    // Roles
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN       = 'admin';
    const ROLE_OPERATOR    = 'operator';
    const ROLE_VIEWER      = 'viewer';

    public function isSuperAdmin(): bool { return $this->role === self::ROLE_SUPER_ADMIN; }
    public function isAdmin(): bool      { return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]); }

    // Relationships
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function assetUnitLogs()
    {
        return $this->hasMany(AssetUnitLog::class, 'performed_by');
    }
}
