<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo TrackingEvent - Eventos de seguimiento de un envío.
 *
 * @property string $id UUID
 * @property string $shipment_id UUID del envío
 * @property int $status_id Estado del catálogo
 * @property string|null $location_name Nombre de la ubicación
 * @property string|null $description Descripción del evento
 * @property string|null $created_by UUID del usuario que creó el evento
 * @property \Carbon\Carbon $timestamp Fecha/hora del evento
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TrackingEvent extends Model
{
    use HasFactory, HasTenant;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'tracking_events';

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
        'shipment_id',
        'status_id',
        'location_name',
        'description',
        'created_by',
        'timestamp',
    ];

    /**
     * Atributos que deben ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
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

        static::creating(function (self $event): void {
            if (empty($event->id)) {
                $event->id = (string) Str::uuid();
            }
            if (empty($event->timestamp)) {
                $event->timestamp = now();
            }
        });
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Envío asociado a este evento.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    /**
     * Estado del evento.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'status_id');
    }

    /**
     * Usuario que creó el evento.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
