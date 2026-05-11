<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo CatalogoValor - Valores individuales de un catálogo.
 *
 * Define valores específicos como estados, tipos, etc.
 *
 * @property int $id
 * @property int $catalogo_id FK a catalogos
 * @property int|null $parent_id Auto-referencia para jerarquía
 * @property string $codigo Código único del valor dentro del catálogo
 * @property string $valor Valor legible
 * @property string|null $descripcion Descripción opcional
 * @property string|null $tenant_id Tenant propietario (null = global)
 * @property bool $is_global Si es visible para todos los tenants
 * @property int $sort_order Orden de visualización
 * @property bool $is_active Si el valor está activo
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
        'tenant_id',
        'is_global',
        'sort_order',
        'is_active',
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
     * Catálogo al que pertenece este valor.
     */
    public function catalogo(): BelongsTo
    {
        return $this->belongsTo(Catalogo::class, 'catalogo_id');
    }

    /**
     * Tenant propietario de este valor.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Valores hijos (para jerarquía).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Valor padre (para jerarquía).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Scope para filtrar por código de catálogo.
     */
    public function scopeByCodigo($query, string $codigo)
    {
        return $query->where('codigo', $codigo);
    }

    /**
     * Scope: Solo valores globales.
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true)->whereNull('tenant_id');
    }

    /**
     * Scope: Valores visibles para un tenant (globales + propios).
     */
    public function scopeVisibleByTenant($query, ?string $tenantId)
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->where('is_global', true)
              ->orWhere('tenant_id', $tenantId);
        });
    }

    /**
     * Scope: Solo valores activos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordenados por sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('valor');
    }

    /**
     * Obtiene el valor formateado para mostrar.
     */
    public function getLabel(): string
    {
        return $this->valor;
    }
}
