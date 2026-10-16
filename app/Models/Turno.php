<?php

namespace App\Models;

use Database\Factories\TurnoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Turno extends Model
{
    /** @use HasFactory<TurnoFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'turnos';

    protected $fillable = ['actividad_id', 'inicio', 'fin', 'cupo'];

    protected function casts(): array
    {
        return ['inicio' => 'datetime', 'fin' => 'datetime', 'cupo' => 'integer'];
    }

    protected static function booted(): void
    {
        // En la estación JOTA cada horario es de un solo equipo: está disponible o está ocupado.
        static::saving(function (Turno $turno) {
            if ($turno->actividad?->esEstacion()) {
                $turno->cupo = 1;
            }
        });
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function haComenzado(): bool
    {
        return $this->inicio->lte(now());
    }

    public function tieneReservas(): bool
    {
        return $this->reservas()->exists();
    }

    /** Auditoría de cambios de configuración: quién cambió qué y cuándo (§4.9). Nunca saldos. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('configuracion')->logOnly(['actividad_id', 'inicio', 'fin', 'cupo'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
