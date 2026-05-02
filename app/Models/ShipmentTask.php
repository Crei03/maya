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

        static::creating(function (self $task): void {
            if (empty($task->id)) {
                $task->id = (string) Str::uuid();
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

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByWarehouse($query, string $warehouseId)
    {
        return $query->where('origin_warehouse_id', $warehouseId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

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
        return $this->hasMany(ShipmentTaskItem::class, 'shipment_task_id')->orderBy('created_at');
    }

    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'shipment_task_items', 'shipment_task_id', 'shipment_id')
            ->withPivot('status', 'delivered_at', 'return_reason')
            ->withTimestamps();
    }

    // ============================================================================
    // Helpers
    // ============================================================================

    public function totalItems(): int
    {
        return $this->items()->count();
    }

    public function deliveredItems(): int
    {
        return $this->items()->where('status', 'entregado')->count();
    }

    public function deliveryRate(): float
    {
        $total = $this->totalItems();

        return $total > 0 ? round(($this->deliveredItems() / $total) * 100, 2) : 0;
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
            'start_date' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'end_date' => now(),
            'total_hours' => $this->start_date?->diffInMinutes(now()) / 60,
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
