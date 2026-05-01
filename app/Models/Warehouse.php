<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Warehouse extends Model
{
    use HasFactory, HasTenant, SoftDeletes;

    protected $table = 'warehouses';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'code',
        'location_address',
        'location_coords',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'location_coords' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot del modelo.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $warehouse): void {
            if (empty($warehouse->id)) {
                $warehouse->id = (string) Str::uuid();
            }
        });
    }

    // ============================================================================
    // Scopes
    // ============================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'warehouse_id');
    }

    public function originTasks(): HasMany
    {
        return $this->hasMany(ShipmentTask::class, 'origin_warehouse_id');
    }
}
