<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Catalogo - Grupos de valores de catálogo.
 *
 * Define categorías de catálogos como estados de envío, tipos de incidente, etc.
 *
 * @property int $id
 * @property string $nombre Nombre del catálogo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Catalogo extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'catalogos';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<string>
     */
    protected $fillable = [
        'nombre',
        'slug',
    ];

    /**
     * Valores asociados a este catálogo.
     */
    public function valores(): HasMany
    {
        return $this->hasMany(CatalogoValor::class, 'catalogo_id');
    }

    /**
     * Obtiene un valor por su código.
     */
    public function getValorByCodigo(string $codigo): ?CatalogoValor
    {
        return $this->valores()->where('codigo', $codigo)->first();
    }
}
