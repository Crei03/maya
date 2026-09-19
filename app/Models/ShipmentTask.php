<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Modelo ShipmentTask - Tareas/rutas de entrega para conductores.
 *
 * @property string $id UUID
 * @property string $tenant_id UUID del tenant
 * @property string $title Nombre descriptivo de la tarea
 * @property string $driver_id UUID del conductor (user con role=messenger)
 * @property string|null $vehicle_id UUID del transporte usado
 * @property string $origin_warehouse_id UUID de la bodega de origen
 * @property \Carbon\Carbon $start_date Fecha y hora de inicio
 * @property \Carbon\Carbon|null $end_date Fecha y hora de finalizacion
 * @property float|null $total_hours Horas totales calculadas al finalizar
 * @property string $status pending|in_progress|completed|cancelled
 * @property string|null $notes Notas opcionales
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ShipmentTask extends Model
{
    use HasFactory, HasTenant, SoftDeletes;

    public const STATUS_PENDIENTE = 'PENDIENTE';

    public const STATUS_EN_PROCESO = 'EN_PROCESO';

    public const STATUS_COMPLETADA = 'COMPLETADA';

    public const STATUS_CANCELADA = 'CANCELADA';

    // Alias de retrocompatibilidad
    public const STATUS_PENDING = self::STATUS_PENDIENTE;

    public const STATUS_IN_PROGRESS = self::STATUS_EN_PROCESO;

    public const STATUS_COMPLETED = self::STATUS_COMPLETADA;

    public const STATUS_CANCELLED = self::STATUS_CANCELADA;

    protected $table = 'shipment_tasks';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'title',
        'driver_id',
        'vehicle_id',
        'origin_warehouse_id',
        'start_date',
        'end_date',
        'total_hours',
        'status',
        'status_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'total_hours' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new \App\Scopes\TenantScope);

        static::creating(function (self $task): void {
            if (empty($task->id)) {
                $task->id = (string) Str::uuid();
            }
            if (empty($task->status_id)) {
                $task->status_id = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-tarea', 'PENDIENTE');
            }
        });
    }

    // ============================================================================
    // Scopes
    // ============================================================================

    public function scopeByDriver($query, string $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeByStatus($query, $status)
    {
        if (is_numeric($status)) {
            return $query->where('status_id', (int) $status);
        }

        return $query->whereHas('status', function ($q) use ($status) {
            $q->where('codigo', strtoupper((string) $status));
        });
    }

    public function scopeByWarehouse($query, string $warehouseId)
    {
        return $query->where('origin_warehouse_id', $warehouseId);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->whereIn('codigo', ['PENDIENTE', 'EN_PROCESO']);
        });
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    public function status(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'status_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentTaskItem::class, 'shipment_task_id')->orderBy('stop_order')->orderBy('created_at');
    }

    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'shipment_task_items', 'shipment_task_id', 'shipment_id')
            ->withPivot('status_id', 'priority_id', 'stop_order', 'delivered_at', 'return_reason')
            ->withTimestamps();
    }

    // ============================================================================
    // Helpers
    // ============================================================================

    /**
     * Genera un código secuencial con formato PLE-YYYY-MM-XXXX único por tenant y mes.
     */
    public static function generateTaskCode(?string $tenantId = null): string
    {
        $prefix = 'PLE';
        $year = date('Y');
        $month = date('m');
        $pattern = "{$prefix}-{$year}-{$month}-%";

        $query = static::withoutGlobalScopes()->where('title', 'like', $pattern);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $lastTask = $query->orderBy('title', 'desc')->first();

        $nextSequence = 1;
        if ($lastTask && preg_match('/-(\d{4})$/', $lastTask->title, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $nextSequence);
    }

    public function totalItems(): int
    {
        return $this->items()->count();
    }

    public function deliveredItems(): int
    {
        return $this->items()->whereHas('status', fn ($q) => $q->where('codigo', 'ENTREGADO'))->count();
    }

    public function deliveryRate(): float
    {
        $total = $this->totalItems();

        return $total > 0 ? round(($this->deliveredItems() / $total) * 100, 2) : 0;
    }

    public function markAsInProgress(): void
    {
        $statusId = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-tarea', 'EN_PROCESO');
        $this->update([
            'status_id' => $statusId,
            'start_date' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $statusId = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-tarea', 'COMPLETADA');
        $this->update([
            'status_id' => $statusId,
            'end_date' => now(),
            'total_hours' => $this->start_date ? round($this->start_date->diffInMinutes(now()) / 60, 2) : 0,
        ]);
    }

    public function markAsCancelled(): void
    {
        $statusId = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-tarea', 'CANCELADA');
        $this->update(['status_id' => $statusId]);
    }

    public function isPending(): bool
    {
        return $this->status?->codigo === 'PENDIENTE';
    }

    public function isInProgress(): bool
    {
        return $this->status?->codigo === 'EN_PROCESO';
    }

    public function isCompleted(): bool
    {
        return $this->status?->codigo === 'COMPLETADA';
    }

    public function isCancelled(): bool
    {
        return $this->status?->codigo === 'CANCELADA';
    }

    public function getStatusCodeAttribute(): string
    {
        return $this->status?->codigo ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->valor ?? '';
    }

    public function setStatusAttribute($value): void
    {
        if (is_numeric($value)) {
            $this->attributes['status_id'] = (int) $value;
        } elseif (is_string($value)) {
            $codeMap = [
                'pending' => 'PENDIENTE',
                'in_progress' => 'EN_PROCESO',
                'completed' => 'COMPLETADA',
                'cancelled' => 'CANCELADA',
            ];
            $code = $codeMap[strtolower($value)] ?? strtoupper($value);
            $this->attributes['status_id'] = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-tarea', $code);
        }
    }
}
