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

/**
 * Modelo Vehicle - Transportes registrados por tenant.
 *
 * @property string      $id               UUID
 * @property string      $tenant_id        UUID del tenant
 * @property string      $license_plate    Matrícula/placa (única por tenant)
 * @property string      $type             internal | external
 * @property string      $brand            Marca (ej: Toyota)
 * @property string      $model            Modelo (ej: Hiace)
 * @property int         $year             Año de fabricación
 * @property float|null  $capacity_kg      Capacidad de carga en kg
 * @property string|null $capacity_volume  Descripción volumétrica
 * @property string|null $color            Color
 * @property bool        $is_active        Activo por defecto
 * @property string|null $notes            Notas
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Vehicle extends Model
{
    use HasFactory, HasTenant, SoftDeletes;

    protected $table = 'vehicles';

    protected $keyType = 'string';

    public $incrementing = false;

    public const TYPE_INTERNAL = 'internal';
    public const TYPE_EXTERNAL = 'external';

    public const TYPES = [
        self::TYPE_INTERNAL,
        self::TYPE_EXTERNAL,
    ];

    public const TYPE_LABELS = [
        self::TYPE_INTERNAL => 'Interno',
        self::TYPE_EXTERNAL => 'Externo',
    ];

    protected $fillable = [
        'id',
        'tenant_id',
        'license_plate',
        'type',
        'brand',
        'model',
        'year',
        'capacity_kg',
        'capacity_volume',
        'color',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'year'            => 'integer',
            'capacity_kg'     => 'decimal:2',
            'is_active'       => 'boolean',
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'deleted_at'      => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $vehicle): void {
            if (empty($vehicle->id)) {
                $vehicle->id = (string) Str::uuid();
            }
        });
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ShipmentTask::class, 'vehicle_id');
    }

    // ============================================================================
    // Helpers
    // ============================================================================

    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function isInternal(): bool
    {
        return $this->type === self::TYPE_INTERNAL;
    }
}
