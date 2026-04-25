<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Client extends Authenticatable
{
    use HasFactory, HasTenant, Notifiable;

    public const ROLE_CLIENT = 'client';

    public const VALID_ROLES = [
        self::ROLE_CLIENT,
    ];

    protected $table = 'clients';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'password',
        'role',
        'phone',
        'business_name',
        'residencia_id',
        'provincia_id',
        'distrito_id',
        'corregimiento_id',
        'calle_id',
        'street_name',
        'street_number',
        'postal_code',
        'status',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Generate UUIDs for new clients.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $client): void {
            if (empty($client->id)) {
                $client->id = (string) Str::uuid();
            }

            if (empty($client->role)) {
                $client->role = self::ROLE_CLIENT;
            }
        });
    }

    public function scopeClients($query)
    {
        return $query->where('role', self::ROLE_CLIENT);
    }

    public function residencia(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'residencia_id');
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'provincia_id');
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'distrito_id');
    }

    public function corregimiento(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'corregimiento_id');
    }

    public function calle(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'calle_id');
    }
}
