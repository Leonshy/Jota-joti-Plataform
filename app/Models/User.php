<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'ci',
        'name',
        'rol',
        'grupo_id',
        'unidad_id',
        'patrulla_id',
    ];

    protected $attributes = [
        'puntos' => 0,
        'jlis' => 0,
    ];

    protected $hidden = [
        'pin_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'rol' => Rol::class,
            'pin_hash' => 'hashed',
            'activado_at' => 'datetime',
            'puntos' => 'integer',
            'jlis' => 'integer',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'pin_hash';
    }

    /** Al panel de administración entra solo el administrador con cuenta activada. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->esAdmin() && $this->activado_at !== null;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function esAdmin(): bool
    {
        return $this->rol === Rol::Admin;
    }

    /** El admin actúa sobre todo; el dirigente solo sobre su unidad (CLAUDE.md §2.7). */
    public function puedeGestionarUnidad(?int $unidadId): bool
    {
        if ($this->esAdmin()) {
            return true;
        }

        return $this->rol === Rol::Dirigente
            && $unidadId !== null
            && $this->unidad_id === $unidadId;
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class);
    }

    public function patrulla(): BelongsTo
    {
        return $this->belongsTo(Patrulla::class);
    }

    /**
     * Auditoría de cambios de configuración de la persona. Solo datos de estructura: nunca el PIN
     * ni los saldos (esos tienen sus propios movimientos).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('configuracion')
            ->logOnly(['ci', 'name', 'rol', 'grupo_id', 'unidad_id', 'patrulla_id'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
