<?php

namespace App\Models;

use App\Enums\Modalidad;
use App\Enums\TipoActividad;
use Database\Factories\ActividadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Actividad extends Model
{
    /** @use HasFactory<ActividadFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'actividades';

    protected $fillable = ['tipo', 'titulo', 'modalidad', 'puntos'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoActividad::class,
            'modalidad' => Modalidad::class,
            'puntos' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // La estación JOTA es siempre por equipo (patrulla, o toda la unidad en los Rovers): no existe «individual».
        static::saving(function (Actividad $actividad) {
            if ($actividad->tipo === TipoActividad::EstacionJota) {
                $actividad->modalidad = Modalidad::Patrulla;
            }
        });
    }

    public function esEstacion(): bool
    {
        return $this->tipo === TipoActividad::EstacionJota;
    }

    public function unidades(): BelongsToMany
    {
        return $this->belongsToMany(Unidad::class, 'actividad_unidad', 'actividad_id', 'unidad_id');
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }

    public function esPorEquipo(): bool
    {
        return $this->modalidad === Modalidad::Patrulla;
    }

    public function esParaUnidad(?int $unidadId): bool
    {
        return $unidadId !== null && $this->unidades()->whereKey($unidadId)->exists();
    }

    /** Con asistencias o cumplimientos, cambiar puntos o modalidad dejaría datos inconsistentes. */
    public function tieneRegistros(): bool
    {
        return DB::table('asistencias')->where('actividad_id', $this->getKey())->exists()
            || DB::table('cumplimientos_equipo')->where('actividad_id', $this->getKey())->exists();
    }

    public function puedeBorrarse(): bool
    {
        return ! $this->tieneRegistros()
            && ! DB::table('sesiones_asistencia')->where('actividad_id', $this->getKey())->exists()
            && ! DB::table('reservas')->whereIn('turno_id', $this->turnos()->select('id'))->exists();
    }

    /** Auditoría de cambios de configuración: quién cambió qué y cuándo (§4.9). Nunca saldos. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('configuracion')->logOnly(['tipo', 'titulo', 'modalidad', 'puntos'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
