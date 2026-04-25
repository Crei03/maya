<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo ServiceRating - Calificaciones de servicio de envíos.
 *
 * @property string $id UUID
 * @property string $shipment_id UUID del envío
 * @property int $rating Calificación 1-5
 * @property string|null $comment Comentario opcional
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ServiceRating extends Model
{
    use HasFactory, HasTenant;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'service_ratings';

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
        'rating',
        'comment',
    ];

    /**
     * Atributos que deben ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
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

        static::creating(function (self $rating): void {
            if (empty($rating->id)) {
                $rating->id = (string) Str::uuid();
            }
        });
    }

    // ============================================================================
    // Scopes
    // ============================================================================

    /**
     * Scope para calificaciones de un envío.
     */
    public function scopeByShipment($query, string $shipmentId)
    {
        return $query->where('shipment_id', $shipmentId);
    }

    /**
     * Scope para calificaciones por rango.
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Envío calificado.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    // ============================================================================
    // Helpers
    // ============================================================================

    /**
     * Obtiene el rating como estrellas (string).
     */
    public function getStarsAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Verifica si es una calificación positiva (4-5).
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }
}
