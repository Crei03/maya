<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property string      $id                     UUID
 * @property string      $tenant_id              UUID del tenant
 * @property string|null $warehouse_id           UUID de la bodega actual
 * @property string|null $assigned_task_id       UUID de la tarea de reparto asignada
 * @property string      $tracking_number        Número de tracking único
 * @property string|null $sender_id              UUID del cliente remitente
 * @property string      $recipient_name         Nombre del destinatario
 * @property string      $recipient_phone        Teléfono del destinatario
 * @property string      $origin_address         Dirección de origen
 * @property string      $destination_address    Dirección de destino
 * @property array|null  $destination_coords     Coordenadas GPS {lat, lng}
 * @property float|null  $weight_kg              Peso en kilogramos
 * @property float|null  $weight_lb              Peso en libras
 * @property float|null  $total_cost             Costo total del envío
 * @property string|null $content_description    Descripción del contenido
 * @property string|null $package_type           Tipo: caja, sobre, palet, etc.
 * @property array|null  $dimensions             Dimensiones en cm {largo, ancho, alto}
 * @property string      $status                 Estado concreto del envío
 * @property int|null    $current_status_id      FK catálogo de estados (legado)
 * @property string|null $label_url              URL de la etiqueta
 * @property string|null $delivered_photo_url    URL de foto de evidencia de entrega
 * @property string|null $recipient_signature_url URL de firma del destinatario
 * @property \Carbon\Carbon|null $eta            Fecha estimada de entrega
 * @property \Carbon\Carbon|null $delivered_at   Fecha y hora real de entrega
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class Shipment extends Model
{
    use HasFactory, HasTenant;

    // ============================================================================
    // Sistema de Estados (Issue #3)
    // ============================================================================

    /** Pendiente / recién creado */
    public const STATUS_PENDING = 'pending';

    /** En bodega */
    public const STATUS_IN_WAREHOUSE = 'in_warehouse';

    /** Asignado a una tarea de reparto */
    public const STATUS_ASSIGNED = 'assigned';

    /** En ruta de entrega */
    public const STATUS_IN_TRANSIT = 'in_transit';

    /** Entregado exitosamente */
    public const STATUS_DELIVERED = 'delivered';

    /** Devuelto a bodega */
    public const STATUS_RETURNED = 'returned';

    /** Fallido / no entregado */
    public const STATUS_FAILED = 'failed';

    /**
     * Todos los estados válidos del envío.
     *
     * @var array<string>
     */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_WAREHOUSE,
        self::STATUS_ASSIGNED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_DELIVERED,
        self::STATUS_RETURNED,
        self::STATUS_FAILED,
    ];

    /**
     * Etiquetas legibles para los estados.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_PENDING      => 'Pendiente',
        self::STATUS_IN_WAREHOUSE => 'En bodega',
        self::STATUS_ASSIGNED     => 'Asignado',
        self::STATUS_IN_TRANSIT   => 'En tránsito',
        self::STATUS_DELIVERED    => 'Entregado',
        self::STATUS_RETURNED     => 'Devuelto',
        self::STATUS_FAILED       => 'Fallido',
    ];

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
        'assigned_task_id',
        'tracking_number',
        'sender_id',
        'recipient_name',
        'recipient_phone',
        'origin_address',
        'destination_address',
        'destination_coords',
        'weight_kg',
        'weight_lb',
        'total_cost',
        'content_description',
        'package_type',
        'dimensions',
        'status',
        'current_status_id',
        'label_url',
        'delivered_photo_url',
        'recipient_signature_url',
        'eta',
        'delivered_at',
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
            'dimensions'         => 'array',
            'weight_kg'          => 'decimal:2',
            'weight_lb'          => 'decimal:2',
            'total_cost'         => 'decimal:2',
            'eta'                => 'datetime',
            'delivered_at'       => 'datetime',
            'created_at'         => 'datetime',
            'updated_at'         => 'datetime',
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
            if (empty($shipment->status)) {
                $shipment->status = self::STATUS_PENDING;
            }
        });
    }

    /**
     * Genera un número de tracking único.
     */
    public static function generateTrackingNumber(): string
    {
        return 'MAYA' . strtoupper(Str::random(10));
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
     * Scope para filtrar por estado concreto.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para paquetes pendientes de asignación (disponibles para despacho).
     */
    public function scopeAvailableForDispatch($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_IN_WAREHOUSE,
        ]);
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
    public function assignedTask(): BelongsTo
    {
        return $this->belongsTo(ShipmentTask::class, 'assigned_task_id');
    }

    /**
     * Estado del catálogo (legado - mantener por compatibilidad).
     */
    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'current_status_id');
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
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Verifica si el envío está disponible para ser asignado a despacho.
     */
    public function isAvailableForDispatch(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_IN_WAREHOUSE,
        ], true);
    }

    /**
     * Retorna la etiqueta legible del estado actual.
     */
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Obtiene el último evento de tracking.
     */
    public function getLastEvent(): ?TrackingEvent
    {
        return $this->trackingEvents()->first();
    }
}
