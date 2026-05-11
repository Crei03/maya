<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo DriverProfile — Perfil extendido de conductor.
 *
 * Extiende la tabla users con campos específicos para conductores (messengers).
 *
 * @property string      $id                UUID
 * @property int         $user_id           FK a users.id
 * @property string|null $license_number    Número de licencia de conducir
 * @property string|null $license_expiry    Fecha de vencimiento de licencia
 * @property string|null $emergency_contact Nombre del contacto de emergencia
 * @property string|null $emergency_phone   Teléfono del contacto de emergencia
 * @property bool        $is_available      Disponible para asignar tareas
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DriverProfile extends Model
{
    use HasFactory;

    protected $table = 'driver_profiles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'phone',
        'license_number',
        'license_expiry',
        'emergency_contact',
        'emergency_phone',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'phone'          => 'string',
            'license_expiry' => 'date',
            'is_available'   => 'boolean',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $profile): void {
            if (empty($profile->id)) {
                $profile->id = (string) Str::uuid();
            }
        });
    }

    // ============================================================================
    // Relaciones
    // ============================================================================

    /**
     * Usuario (conductor) dueño de este perfil.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ============================================================================
    // Helpers
    // ============================================================================

    /**
     * Indica si la licencia está vencida.
     */
    public function isLicenseExpired(): bool
    {
        if ($this->license_expiry === null) {
            return false;
        }

        return $this->license_expiry->isPast();
    }
}
