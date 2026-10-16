<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

class SesionAsistencia extends Model
{
    protected $table = 'sesiones_asistencia';

    protected $fillable = ['actividad_id', 'turno_id', 'abierta_por', 'secreto', 'abierta_at', 'cerrada_at'];

    protected $hidden = ['secreto'];

    protected function casts(): array
    {
        return ['abierta_at' => 'datetime', 'cerrada_at' => 'datetime'];
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'sesion_id');
    }

    /** Enlace público firmado de la pantalla con el QR rotativo; deja de servir al cerrar la sesión. */
    public function urlPublica(): string
    {
        return URL::signedRoute('asistencia.publica', ['sesion' => $this]);
    }

    public function estaAbierta(): bool
    {
        return $this->cerrada_at === null;
    }
}
