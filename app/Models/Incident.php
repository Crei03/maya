<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo Incident - Incidentes reportados durante entregas.
 *
 * @property string $id UUID
 * @property string $shipment_id UUID del envío afectado
 * @property string|null $messenger_id UUID del mensajero que reportó
 * @property int $type_id Tipo de incidente (catálogo)
 * @property string|null $description Descripción del incidente
 * @property string|null $photo_evidence_url URL de evidencia fotográfica
 * @property mixed|null $gps_location Coordenadas GPS
 * @property bool $resolved Indica si fue resuelto
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Incident extends Model
{
    use HasFactory, HasTenant;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'incidents';

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
        'messenger_id',
        'type_id',
        'description',
        'photo_evidence_url',
        'gps_location',
        'resolved',
    ];

    /**
     * Atributos que deben ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gps_location' => 'array',
            'resolved' => 'boolean',
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

        static::creating(function (self $incident): void {
            if (empty($incident->id)) {
                $incident->id = (string) Str::uuid();
            }
        });
    }

    // ============================================================================
    // Scopes
    // ============================================================================

    /**
     * Scope para incidentes de un envío.
     */
    public function scopeByShipment($query, string $shipmentId)
    {
        return $query->where('shipment_id', $shipmentId);
    }

    /**
     * Scope para incidentes de un mensajero.
     */
    public function scopeByMessenger($query, string $messengerId)
    {
        return $query->where('messenger_id', $messengerId);
    }

    /**
     * Scope para incidentes abiertos (no resueltos).
     */
    public function scopeOpen($query)
    {
        return $query->where('resolved', 0);
    }

    /**
     * Scope para incidentes resueltos.
     */
    public function scopeResolved($query)
    {
        return $query->where('resolved', 1);
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Envío afectado.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    /**
     * Mensajero que reportó.
     */
    public function messenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'messenger_id');
    }

    /**
     * Tipo de incidente.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'type_id');
    }

    // ============================================================================
    // Helpers
    // ============================================================================

    /**
     * Marca el incidente como resuelto.
     */
    public function resolve(): void
    {
        $this->update(['resolved' => 1]);
    }
}
