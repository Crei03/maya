<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\TenantScope;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Modelo Shipment - Envíos de paquetes.
 *
 * Representa un envío desde origen hasta destino con tracking completo.
 *
 * @property string $id UUID
 * @property string $tenant_id UUID del tenant
 * @property string|null $warehouse_id UUID de la bodega actual
 * @property string|null $driver_task UUID de la tarea de reparto asignada
 * @property string $tracking_number Número de tracking único
 * @property string|null $sender_id UUID del cliente remitente
 * @property string $origin_address Dirección de origen (derivada de warehouse)
 * @property string $destination_address Dirección de destino
 * @property array|null $destination_coords Coordenadas GPS {lat, lng}
 * @property float|null $weight_kg Peso en kilogramos
 * @property float $weight_lb Peso en libras
 * @property float|null $total_cost Costo total del envío
 * @property string|null $content_description Descripción del contenido
 * @property string|null $package_type Tipo: caja, sobre, palet, etc.
 * @property array|null $dimensions Dimensiones en cm {largo, ancho, alto}
 * @property string $status Estado concreto del envío
 * @property string|null $label_url URL de la etiqueta
 * @property string|null $delivered_photo_url URL de foto de evidencia de entrega
 * @property string|null $recipient_signature_url URL de firma del destinatario
 * @property \Carbon\Carbon|null $eta Fecha estimada de entrega
 * @property \Carbon\Carbon|null $delivered_at Fecha y hora real de entrega
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Shipment extends Model
{
    use HasFactory, HasTenant;

    // ============================================================================
    // Sistema de Estados Estandarizados (Catálogo en BD)
    // ============================================================================

    public const STATUS_PENDIENTE = 'PENDIENTE';

    public const STATUS_EN_BODEGA = 'EN_BODEGA';

    public const STATUS_ASIGNADO = 'ASIGNADO';

    public const STATUS_EN_TRANSITO = 'EN_TRANSITO';

    public const STATUS_ENTREGADO = 'ENTREGADO';

    public const STATUS_DEVUELTO = 'DEVUELTO';

    public const STATUS_FALLIDO = 'FALLIDO';

    public const STATUS_CANCELADO = 'CANCELADO';

    // Alias retrocompatibles
    public const STATUS_PENDING = self::STATUS_PENDIENTE;

    public const STATUS_IN_WAREHOUSE = self::STATUS_EN_BODEGA;

    public const STATUS_ASSIGNED = self::STATUS_ASIGNADO;

    public const STATUS_IN_TRANSIT = self::STATUS_EN_TRANSITO;

    public const STATUS_DELIVERED = self::STATUS_ENTREGADO;

    public const STATUS_RETURNED = self::STATUS_DEVUELTO;

    public const STATUS_FAILED = self::STATUS_FALLIDO;

    // ============================================================================
    // Tipos de Documento de Referencia (Catálogo en BD)
    // ============================================================================

    public const REF_TYPE_PEDIDO = 'PEDIDO';

    public const REF_TYPE_FACTURA = 'FACTURA';

    public const REF_TYPE_TRANSFERENCIA = 'TRANSFERENCIA';

    public const REF_TYPE_RECIBO = 'RECIBO';

    public const REF_TYPE_GUIA = 'GUIA';

    public const REF_TYPE_LPN = 'LPN';

    public const REF_TYPE_OTRO = 'OTRO';

    // ============================================================================
    // Configuración del modelo
    // ============================================================================

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'shipments';

    /**
     * Tipo de clave primaria.
     */
    protected $keyType = 'string';

    /**
     * Indica si la clave primaria es autoincremental.
     */
    public $incrementing = false;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'warehouse_id',
        'driver_task',
        'tracking_number',
        'status',
        'status_id',
        'reference_type',
        'reference_type_id',
        'reference_number',
        'lpn_code',
        'pieces_count',
        'sender_id',
        'recipient_name',
        'recipient_phone',
        'destination_address',
        'destination_coords',
        'weight_kg',
        'weight_lb',
        'total_cost',
        'content_description',
        'package_type',
        'package_type_id',
        'dimensions',
        'label_url',
        'delivered_photo_url',
        'recipient_signature_url',
        'eta',
        'delivered_at',
    ];

    /**
     * Atributos que se incluyen en la serialización.
     *
     * @var array<string>
     */
    protected $appends = [
        'origin_address',
        'status_code',
    ];

    /**
     * Atributos que deben ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'destination_coords' => 'array',
            'dimensions' => 'array',
            'pieces_count' => 'integer',
            'weight_kg' => 'decimal:2',
            'weight_lb' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'eta' => 'datetime',
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot del modelo.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $shipment): void {
            if (empty($shipment->id)) {
                $shipment->id = (string) Str::uuid();
            }
            if (empty($shipment->tracking_number)) {
                $shipment->tracking_number = self::generateTrackingNumber();
            }
            if (empty($shipment->status_id)) {
                $shipment->status_id = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-envio', 'PENDIENTE');
            }
        });
    }

    /**
     * Apply global tenant scope to all queries.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Genera un número de tracking único.
     */
    public static function generateTrackingNumber(): string
    {
        return 'MAYA'.strtoupper(Str::random(10));
    }

    // ============================================================================
    // Scopes
    // ============================================================================

    /**
     * Scope para buscar por número de tracking.
     */
    public function scopeByTracking($query, string $trackingNumber)
    {
        return $query->where('tracking_number', $trackingNumber);
    }

    /**
     * Scope para buscar por código LPN o Pallet.
     */
    public function scopeByLpn($query, string $lpn)
    {
        return $query->where('lpn_code', $lpn);
    }

    /**
     * Scope para buscar por número de documento de referencia WMS.
     */
    public function scopeByReference($query, string $ref)
    {
        return $query->where('reference_number', $ref);
    }

    /**
     * Scope para filtrar por estado concreto (id numérico o código string).
     */
    public function scopeByStatus($query, $status)
    {
        if (is_numeric($status)) {
            return $query->where('status_id', (int) $status);
        }

        return $query->whereHas('status', function ($q) use ($status) {
            $q->where('codigo', strtoupper((string) $status));
        });
    }

    /**
     * Scope para paquetes pendientes de asignación (disponibles para despacho).
     */
    public function scopeAvailableForDispatch($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->whereIn('codigo', [self::STATUS_PENDIENTE, self::STATUS_EN_BODEGA]);
        });
    }

    /**
     * Scope para envíos de un remitente.
     */
    public function scopeBySender($query, string $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    /**
     * Scope para envíos en una bodega específica.
     */
    public function scopeInWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Estado del ciclo de vida del paquete (Catálogo).
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'status_id');
    }

    /**
     * Tipo de documento de referencia (Catálogo).
     */
    public function referenceType(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'reference_type_id');
    }

    /**
     * Formato o tipo de paquete físico (Catálogo).
     */
    public function packageType(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'package_type_id');
    }

    /**
     * Cliente remitente del envío.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'sender_id');
    }

    /**
     * Bodega donde se encuentra el paquete actualmente.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Tarea de reparto asignada al envío.
     */
    public function driverTask(): BelongsTo
    {
        return $this->belongsTo(ShipmentTask::class, 'driver_task');
    }

    /**
     * Eventos de tracking del envío.
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class, 'shipment_id')->orderBy('timestamp', 'desc');
    }

    /**
     * Prueba de entrega del envío.
     */
    public function deliveryProof(): HasOne
    {
        return $this->hasOne(DeliveryProof::class, 'shipment_id');
    }

    /**
     * Calificación del servicio.
     */
    public function serviceRating(): HasOne
    {
        return $this->hasOne(ServiceRating::class, 'shipment_id');
    }

    /**
     * Incidentes asociados al envío.
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'shipment_id');
    }

    /**
     * Items de manifiesto donde aparece este envío.
     */
    public function manifestItems(): HasMany
    {
        return $this->hasMany(ManifestItem::class, 'shipment_id');
    }

    /**
     * Items de tareas de envío donde aparece este envío.
     */
    public function shipmentTaskItems(): HasMany
    {
        return $this->hasMany(ShipmentTaskItem::class, 'shipment_id');
    }

    // ============================================================================
    // Helpers
    // ============================================================================

    /**
     * Verifica si el envío fue entregado exitosamente.
     */
    public function isDelivered(): bool
    {
        $code = is_string($this->status) ? $this->status : $this->status?->codigo;

        return $code === self::STATUS_ENTREGADO;
    }

    /**
     * Verifica si el envío está disponible para ser asignado a despacho.
     */
    public function isAvailableForDispatch(): bool
    {
        $code = is_string($this->status) ? $this->status : $this->status?->codigo;

        return in_array($code, [
            self::STATUS_PENDIENTE,
            self::STATUS_EN_BODEGA,
        ], true);
    }

    /**
     * Retorna la etiqueta legible del estado actual.
     */
    public function getStatusLabel(): string
    {
        return $this->status?->valor ?? '';
    }

    /**
     * Obtiene el último evento de tracking.
     */
    public function getLastEvent(): ?TrackingEvent
    {
        return $this->trackingEvents()->first();
    }

    // ============================================================================
    // Accessors
    // ============================================================================

    /**
     * Obtiene la dirección de origen derivada de la bodega.
     */
    public function getOriginAddressAttribute(): string
    {
        return $this->warehouse?->location_address ?? '';
    }

    /**
     * Código de estado legible para backward compatibility.
     */
    public function getStatusCodeAttribute(): string
    {
        return $this->status?->codigo ?? '';
    }

    public function setStatusAttribute($value): void
    {
        if (is_numeric($value)) {
            $this->attributes['status_id'] = (int) $value;
        } elseif (is_string($value)) {
            $this->attributes['status_id'] = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('estado-envio', strtoupper($value));
        }
    }

    public function setPackageTypeAttribute($value): void
    {
        if (is_numeric($value)) {
            $this->attributes['package_type_id'] = (int) $value;
        } elseif (is_string($value)) {
            $this->attributes['package_type_id'] = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('tipo-paquete', strtoupper($value));
        }
    }

    public function setReferenceTypeAttribute($value): void
    {
        if (is_numeric($value)) {
            $this->attributes['reference_type_id'] = (int) $value;
        } elseif (is_string($value)) {
            $this->attributes['reference_type_id'] = app(\App\Services\CatalogoService::class)->getValorIdByCodigo('tipo-referencia', strtoupper($value));
        }
    }
}
