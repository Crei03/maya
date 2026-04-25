<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo PickupRequest - Solicitudes de recolección de paquetes.
 *
 * @property string $id UUID
 * @property string|null $requester_id UUID del solicitante
 * @property string $pickup_address Dirección de recolección
 * @property mixed|null $pickup_coords Coordenadas GPS
 * @property \Carbon\Carbon|null $scheduled_date Fecha programada
 * @property int|null $status_id Estado de la solicitud
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PickupRequest extends Model
{
    use HasFactory, HasTenant;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'pickup_requests';

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
        'requester_id',
        'pickup_address',
        'pickup_coords',
        'scheduled_date',
        'status_id',
    ];

    /**
     * Atributos que deben ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pickup_coords' => 'array',
            'scheduled_date' => 'datetime',
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

        static::creating(function (self $request): void {
            if (empty($request->id)) {
                $request->id = (string) Str::uuid();
            }
        });
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Solicitante.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Estado de la solicitud.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(CatalogoValor::class, 'status_id');
    }
}
