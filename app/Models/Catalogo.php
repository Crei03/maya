<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Catalogo - Grupos de valores de catálogo.
 *
 * Define categorías de catálogos como estados de envío, tipos de incidente, etc.
 *
 * @property int $id
 * @property string $nombre Nombre del catálogo
 * @property string $slug Identificador único del catálogo
 * @property string|null $description Descripción del catálogo
 * @property bool $is_global Si es visible para todos los tenants
 * @property bool $is_active Si el catálogo está activo
 * @property int $sort_order Orden de visualización
 * @property string|null $tenant_id Tenant propietario (null = global)
 * @property int|null $created_by Usuario que creó el catálogo
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
        'description',
        'is_global',
        'is_active',
        'sort_order',
        'tenant_id',
        'created_by',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Valores asociados a este catálogo.
     */
    public function valores(): HasMany
    {
        return $this->hasMany(CatalogoValor::class, 'catalogo_id');
    }

    /**
     * Usuario que creó este catálogo.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Tenant propietario de este catálogo.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Obtiene un valor por su código.
     */
    public function getValorByCodigo(string $codigo): ?CatalogoValor
    {
        return $this->valores()->where('codigo', $codigo)->first();
    }

    /**
     * Scope: Solo catálogos globales.
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope: Catálogos visibles para un tenant (globales + propios).
     */
    public function scopeVisibleByTenant($query, ?string $tenantId)
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->where('is_global', true)
              ->orWhere('tenant_id', $tenantId);
        });
    }
}
