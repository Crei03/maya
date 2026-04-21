<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo CatalogoValor - Valores individuales de un catálogo.
 *
 * Define valores específicos como estados, tipos, etc.
 *
 * @property int $id
 * @property int $catalogo_id FK a catalogos
 * @property string $codigo Código único del valor
 * @property string $valor Valor legible
 * @property string|null $descripcion Descripción opcional
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CatalogoValor extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'catalogo_valores';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<string>
     */
    protected $fillable = [
        'catalogo_id',
        'parent_id',
        'codigo',
        'valor',
        'descripcion',
    ];

    /**
     * Catálogo al que pertenece este valor.
     */
    public function catalogo(): BelongsTo
    {
        return $this->belongsTo(Catalogo::class, 'catalogo_id');
    }

    /**
     * Scope para filtrar por código de catálogo.
     */
    public function scopeByCodigo($query, string $codigo)
    {
        return $query->where('codigo', $codigo);
    }

    /**
     * Obtiene el valor formateado para mostrar.
     */
    public function getLabel(): string
    {
        return $this->valor;
    }
}
