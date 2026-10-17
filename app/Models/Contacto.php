<?php

namespace App\Models;

use App\Enums\EstadoEntrega;
use App\Enums\TipoContacto;
use Database\Factories\ContactoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contacto extends Model
{
    /** @use HasFactory<ContactoFactory> */
    use HasFactory;

    protected $table = 'contactos';

    protected $fillable = [
        'tipo', 'user_id', 'unidad_id', 'patrulla_id', 'es_local', 'estado', 'puntos', 'revisado_por', 'revisado_at', 'motivo_rechazo',
        'grupo_scout', 'ciudad', 'unidad_scout', 'pais', 'captura_ruta', 'captura_procesada_at',
        'contactado_at', 'estacion', 'rst_tx', 'rst_rx', 'frecuencia', 'emision', 'obs', 'qsl_r', 'qsl_e',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoContacto::class,
            'estado' => EstadoEntrega::class,
            'es_local' => 'boolean',
            'puntos' => 'integer',
            'revisado_at' => 'datetime',
            'captura_procesada_at' => 'datetime',
            'contactado_at' => 'datetime',
            'qsl_r' => 'boolean',
            'qsl_e' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class);
    }

    public function patrulla(): BelongsTo
    {
        return $this->belongsTo(Patrulla::class);
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    /** @param  Builder<Contacto>  $q */
    public function scopePendientes(Builder $q): Builder
    {
        return $q->where('estado', EstadoEntrega::Pendiente);
    }

    public function esJota(): bool
    {
        return $this->tipo === TipoContacto::Jota;
    }
}
