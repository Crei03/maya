<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo AuditLog - Registro de auditoría del sistema.
 *
 * @property string $id UUID
 * @property string|null $user_id UUID del usuario que realizó la acción
 * @property string $action Acción realizada
 * @property string $entity_type Tipo de entidad afectada
 * @property string|null $entity_id ID de la entidad
 * @property array|null $old_values Valores anteriores
 * @property array|null $new_values Valores nuevos
 * @property string|null $ip_address IP del usuario
 * @property string|null $user_agent User agent
 * @property \Carbon\Carbon $created_at
 */
class AuditLog extends Model
{
    use HasFactory, HasTenant;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'audit_logs';

    /**
     * Tipo de clave primaria.
     */
    protected $keyType = 'string';

    /**
     * Indica si la clave primaria es autoincremental.
     */
    public $incrementing = false;

    /**
     * No usar timestamps automáticos (solo created_at).
     */
    public $timestamps = false;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * Atributos que deben ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Boot del modelo.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $log): void {
            if (empty($log->id)) {
                $log->id = (string) Str::uuid();
            }
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Usuario que realizó la acción.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ============================================================================
    // Scopes
    // ============================================================================

    /**
     * Scope para acciones de una entidad.
     */
    public function scopeForEntity($query, string $type, string $id)
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }

    /**
     * Scope para acciones de un usuario.
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}
