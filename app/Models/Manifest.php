<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Modelo Manifest - Manifiestos de entrega para mensajeros.
 *
 * @property string $id UUID
 * @property string|null $messenger_id UUID del mensajero
 * @property int|null $status_id Estado del manifiesto
 * @property string|null $vehicle_id Identificador del vehículo
 * @property \Carbon\Carbon|null $scheduled_date Fecha programada
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Manifest extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'manifests';

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
        'messenger_id',
        'status_id',
        'vehicle_id',
        'scheduled_date',
    ];

    /**
     * Atributos que deben ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
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

        static::creating(function (self $manifest): void {
            if (empty($manifest->id)) {
                $manifest->id = (string) Str::uuid();
            }
        });
    }

    // ============================================================================
    // Scopes
    // ============================================================================

    /**
     * Scope para manifiestos de un mensajero.
     */
    public function scopeByMessenger($query, string $messengerId)
    {
        return $query->where('messenger_id', $messengerId);
    }

    /**
     * Scope para manifiestos por fecha programada.
     */
    public function scopeByDate($query, string $date)
    {
        return $query->whereDate('scheduled_date', $date);
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Mensajero asignado.
     */
    public function messenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'messenger_id');
    }

    /**
     * Estado del manifiesto.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'status_id');
    }

    /**
     * Items del manifiesto (envíos).
     */
    public function items(): HasMany
    {
        return $this->hasMany(ManifestItem::class, 'manifest_id')->orderBy('stop_order');
    }

    // ============================================================================
    // Helpers
    // ============================================================================

    /**
     * Obtiene el total de items.
     */
    public function totalItems(): int
    {
        return $this->items()->count();
    }

    /**
     * Obtiene el total de items entregados.
     */
    public function deliveredItems(): int
    {
        return $this->items()->where('is_delivered', 1)->count();
    }

    /**
     * Obtiene el porcentaje de entregas exitosas.
     */
    public function deliveryRate(): float
    {
        $total = $this->totalItems();

        return $total > 0 ? round(($this->deliveredItems() / $total) * 100, 2) : 0;
    }
}
