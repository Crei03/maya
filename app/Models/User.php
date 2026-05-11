<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasTenant, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'tenant_id',
    ];

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_GESTOR = 'gestor';
    public const ROLE_MESSENGER = 'messenger';
    public const ROLE_CLIENT = 'client';
    public const ROLE_ADMIN = 'admin'; // @deprecated Use ROLE_GESTOR instead

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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function manifests(): HasMany
    {
        return $this->hasMany(Manifest::class, 'messenger_id');
    }

    public function shipmentTasks(): HasMany
    {
        return $this->hasMany(ShipmentTask::class, 'driver_id');
    }

    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class, 'user_id');
    }

    public function isManagement(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isGestor(): bool
    {
        return $this->role === self::ROLE_GESTOR || $this->role === self::ROLE_ADMIN;
    }

    public function isMessenger(): bool
    {
        return $this->role === self::ROLE_MESSENGER;
    }

    public function scopeMessengers($query)
    {
        return $query->where('role', self::ROLE_MESSENGER);
    }
}
