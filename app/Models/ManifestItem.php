<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo ManifestItem - Items de un manifiesto (envíos asignados).
 *
 * Esta tabla es clave para calcular el Delivery Rate (FADR).
 *
 * @property string $manifest_id UUID del manifiesto
 * @property string $shipment_id UUID del envío
 * @property int $stop_order Orden de parada en la ruta
 * @property bool $is_delivered 1=entregado exitosamente, 0=fallido
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ManifestItem extends Model
{
    use HasFactory, HasTenant;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'manifest_items';

    /**
     * No usar timestamps automáticos.
     */
    public $timestamps = true;

    /**
     * No tiene clave primaria autoincremental.
     */
    public $incrementing = false;

    /**
     * Clave primaria compuesta.
     */
    protected $primaryKey = null;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'manifest_id',
        'shipment_id',
        'stop_order',
        'is_delivered',
    ];

    /**
     * Atributos que deben ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_delivered' => 'boolean',
            'stop_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ============================================================================
    // Scopes
    // ============================================================================

    /**
     * Scope para items de un manifiesto.
     */
    public function scopeByManifest($query, string $manifestId)
    {
        return $query->where('manifest_id', $manifestId);
    }

    /**
     * Scope para items entregados exitosamente.
     */
    public function scopeDelivered($query)
    {
        return $query->where('is_delivered', 1);
    }

    /**
     * Scope para items fallidos.
     */
    public function scopeFailed($query)
    {
        return $query->where('is_delivered', 0);
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Manifiesto al que pertenece.
     */
    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class, 'manifest_id');
    }

    /**
     * Envío asignado.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    // ============================================================================
    // Helpers
    // ============================================================================

    /**
     * Marca como entregado exitosamente.
     */
    public function markAsDelivered(): void
    {
        $this->update(['is_delivered' => 1]);
    }

    /**
     * Marca como fallido.
     */
    public function markAsFailed(): void
    {
        $this->update(['is_delivered' => 0]);
    }
}
