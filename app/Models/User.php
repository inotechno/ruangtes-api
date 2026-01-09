<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'userable_type',
        'userable_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userable(): MorphTo
    {
        return $this->morphTo();
    }

    public function companyAdmin(): ?CompanyAdmin
    {
        return $this->userable instanceof CompanyAdmin ? $this->userable : null;
    }

    public function publicUser(): ?PublicUser
    {
        return $this->userable instanceof PublicUser ? $this->userable : null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isTenantAdmin(): bool
    {
        return $this->hasRole('tenant_admin');
    }

    public function isPublicUser(): bool
    {
        return $this->hasRole('public_user');
    }
}
