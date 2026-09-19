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
 * @property string $priority alta|media|baja
 * @property int $stop_order Orden de entrega
 * @property \Carbon\Carbon|null $delivered_at Fecha de entrega
 * @property string|null $return_reason Motivo de retorno
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ShipmentTaskItem extends Model
{
    use HasFactory, HasTenant;

    public const PRIORITY_ALTA = 'ALTA';

    public const PRIORITY_MEDIA = 'MEDIA';

    public const PRIORITY_BAJA = 'BAJA';

    public const PRIORITIES = [
        self::PRIORITY_ALTA,
        self::PRIORITY_MEDIA,
        self::PRIORITY_BAJA,
        'alta',
        'media',
        'baja',
    ];

    public const STATUS_PENDIENTE = 'PENDIENTE';

    public const STATUS_ENTREGADO = 'ENTREGADO';

    public const STATUS_RETORNADO = 'RETORNADO';

    protected $table = 'shipment_task_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'shipment_task_id',
        'shipment_id',
        'status',
        'status_id',
        'priority',
        'priority_id',
        'stop_order',
        'delivered_at',
        'return_reason',
    ];

    protected function casts(): array
    {
        return [
            'stop_order' => 'integer',
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
            if (empty($item->status_id)) {
                $item->status_id = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-item-tarea', 'PENDIENTE');
            }
            if (empty($item->priority_id)) {
                $item->priority_id = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('prioridad-tarea', 'MEDIA');
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
        return $query->whereHas('status', function ($q) {
            $q->where('codigo', 'ENTREGADO');
        });
    }

    public function scopePending($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->where('codigo', 'PENDIENTE');
        });
    }

    public function scopeReturned($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->where('codigo', 'RETORNADO');
        });
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    public function status(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'status_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'priority_id');
    }

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
        $statusId = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-item-tarea', 'ENTREGADO');
        $this->update([
            'status_id' => $statusId,
            'delivered_at' => now(),
        ]);
    }

    public function markAsReturned(string $reason = ''): void
    {
        $statusId = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-item-tarea', 'RETORNADO');
        $this->update([
            'status_id' => $statusId,
            'return_reason' => $reason ?: $this->return_reason,
        ]);
    }

    public function markAsPending(): void
    {
        $statusId = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-item-tarea', 'PENDIENTE');
        $this->update([
            'status_id' => $statusId,
            'delivered_at' => null,
            'return_reason' => null,
        ]);
    }

    public function isDelivered(): bool
    {
        return $this->status?->codigo === 'ENTREGADO';
    }

    public function isPending(): bool
    {
        return $this->status?->codigo === 'PENDIENTE';
    }

    public function isReturned(): bool
    {
        return $this->status?->codigo === 'RETORNADO';
    }

    public function getStatusCodeAttribute(): string
    {
        return $this->status?->codigo ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->valor ?? '';
    }

    public function getPriorityCodeAttribute(): string
    {
        return $this->priority?->codigo ?? '';
    }

    public function getPriorityLabelAttribute(): string
    {
        return $this->priority?->valor ?? '';
    }

    public function setStatusAttribute($value): void
    {
        if (is_numeric($value)) {
            $this->attributes['status_id'] = (int) $value;
        } elseif (is_string($value)) {
            $codeMap = [
                'pendiente' => 'PENDIENTE',
                'entregado' => 'ENTREGADO',
                'retornado' => 'RETORNADO',
            ];
            $code = $codeMap[strtolower($value)] ?? strtoupper($value);
            $this->attributes['status_id'] = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-item-tarea', $code);
        }
    }

    public function setPriorityAttribute($value): void
    {
        if (is_numeric($value)) {
            $this->attributes['priority_id'] = (int) $value;
        } elseif (is_string($value)) {
            $codeMap = [
                'alta' => 'ALTA',
                'media' => 'MEDIA',
                'baja' => 'BAJA',
            ];
            $code = $codeMap[strtolower($value)] ?? strtoupper($value);
            $this->attributes['priority_id'] = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('prioridad-tarea', $code);
        }
    }
}
