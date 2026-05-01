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
 * Representa un envío desde origen hasta destino con tracking.
 *
 * @property string $id UUID
 * @property string $tracking_number Número de tracking único
 * @property string|null $sender_id UUID del remitente
 * @property string $recipient_name Nombre del destinatario
 * @property string $recipient_phone Teléfono del destinatario
 * @property string $origin_address Dirección de origen
 * @property string $destination_address Dirección de destino
 * @property mixed $destination_coords Coordenadas GPS (POINT)
 * @property float|null $weight_kg Peso en kilogramos
 * @property float|null $total_cost Costo total del envío
 * @property int|null $current_status_id Estado actual del catálogo
 * @property \Carbon\Carbon|null $eta Fecha estimada de entrega
 * @property string|null $label_url URL de la etiqueta
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Shipment extends Model
{
    use HasFactory, HasTenant;

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
        'tracking_number',
        'sender_id',
        'recipient_name',
        'recipient_phone',
        'origin_address',
        'destination_address',
        'destination_coords',
        'weight_kg',
        'total_cost',
        'current_status_id',
        'eta',
        'label_url',
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
            'weight_kg' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'eta' => 'datetime',
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
     * Scope para filtrar por estado.
     */
    public function scopeByStatus($query, int $statusId)
    {
        return $query->where('current_status_id', $statusId);
    }

    /**
     * Scope para envíos de un remitente.
     */
    public function scopeBySender($query, string $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Remitente del envío.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Estado actual del envío.
     */
    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'current_status_id');
    }

    /**
     * Bodega donde se encuentra el paquete.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
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

    // ============================================================================
    // Helpers
    // ============================================================================

    /**
     * Verifica si el envío está entregado.
     */
    public function isDelivered(): bool
    {
        // TODO: Verificar contra catálogo de estados
        return false;
    }

    /**
     * Obtiene el último evento de tracking.
     */
    public function getLastEvent(): ?TrackingEvent
    {
        return $this->trackingEvents()->first();
    }
}
