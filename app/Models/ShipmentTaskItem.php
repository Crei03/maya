<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo ShipmentTaskItem - Items de una tarea de envio (envios asignados).
 *
 * @property string $id UUID
 * @property string $tenant_id UUID del tenant
 * @property string $shipment_task_id UUID de la tarea
 * @property string $shipment_id UUID del envio
 * @property string $status pendiente|entregado|retornado
 * @property \Carbon\Carbon|null $delivered_at Fecha de entrega
 * @property string|null $return_reason Motivo de retorno
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ShipmentTaskItem extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'shipment_task_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'shipment_task_id',
        'shipment_id',
        'status',
        'delivered_at',
        'return_reason',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $item): void {
            if (empty($item->id)) {
                $item->id = (string) Str::uuid();
            }
        });
    }

    // ============================================================================
    // Scopes
    // ============================================================================

    public function scopeByTask($query, string $taskId)
    {
        return $query->where('shipment_task_id', $taskId);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'entregado');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'retornado');
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    public function task(): BelongsTo
    {
        return $this->belongsTo(ShipmentTask::class, 'shipment_task_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    // ============================================================================
    // Helpers
    // ============================================================================

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'entregado',
            'delivered_at' => now(),
        ]);
    }

    public function markAsReturned(string $reason = ''): void
    {
        $this->update([
            'status' => 'retornado',
            'return_reason' => $reason ?: $this->return_reason,
        ]);
    }

    public function markAsPending(): void
    {
        $this->update([
            'status' => 'pendiente',
            'delivered_at' => null,
            'return_reason' => null,
        ]);
    }

    public function isDelivered(): bool
    {
        return $this->status === 'entregado';
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function isReturned(): bool
    {
        return $this->status === 'retornado';
    }
}
