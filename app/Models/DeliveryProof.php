<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo DeliveryProof - Pruebas de entrega de envíos.
 *
 * @property string $id UUID
 * @property string $shipment_id UUID del envío
 * @property string|null $signature_url URL de la firma digital
 * @property string|null $photo_url URL de la foto de entrega
 * @property mixed|null $gps_coords Coordenadas GPS (POINT)
 * @property string|null $signed_by Nombre de quien firmó
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DeliveryProof extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'delivery_proofs';

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
        'shipment_id',
        'signature_url',
        'photo_url',
        'gps_coords',
        'signed_by',
    ];

    /**
     * Atributos que deben ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gps_coords' => 'array',
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

        static::creating(function (self $proof): void {
            if (empty($proof->id)) {
                $proof->id = (string) Str::uuid();
            }
        });
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Envío asociado.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}
