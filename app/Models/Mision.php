<?php

namespace App\Models;

use App\Enums\Modalidad;
use Database\Factories\MisionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Mision extends Model
{
    /** @use HasFactory<MisionFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'misiones';

    protected $fillable = ['titulo', 'lugar', 'descripcion', 'modalidad', 'puntos', 'lanza_at', 'vence_at', 'cerrada_at'];

    protected function casts(): array
    {
        return [
            'modalidad' => Modalidad::class,
            'puntos' => 'integer',
            'lanza_at' => 'datetime',
            'vence_at' => 'datetime',
            'cerrada_at' => 'datetime',
        ];
    }

    public function unidades(): BelongsToMany
    {
        return $this->belongsToMany(Unidad::class, 'mision_unidad', 'mision_id', 'unidad_id');
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class);
    }

    /** Misiones que la unidad del usuario ya puede ver: es destino y pasó la hora de lanzamiento. */
    public function scopeVisiblesPara(Builder $query, User $user): void
    {
        $query->where('lanza_at', '<=', now())
            ->whereHas('unidades', fn (Builder $q) => $q->whereKey($user->unidad_id));
    }

    public function estaLanzada(): bool
    {
        return $this->lanza_at->lte(now());
    }

    /** Se puede entregar solo si ya se lanzó y no está vencida ni cerrada (hora del servidor). */
    public function estaAbierta(): bool
    {
        return $this->estaLanzada()
            && $this->cerrada_at === null
            && ($this->vence_at === null || $this->vence_at->isFuture());
    }

    public function esPorPatrulla(): bool
    {
        return $this->modalidad === Modalidad::Patrulla;
    }

    /** programada | abierta | vencida | cerrada, para mostrar en el panel. */
    public function estado(): string
    {
        return match (true) {
            $this->cerrada_at !== null => 'cerrada',
            ! $this->estaLanzada() => 'programada',
            $this->vence_at !== null && ! $this->vence_at->isFuture() => 'vencida',
            default => 'abierta',
        };
    }

    public function tieneEntregas(): bool
    {
        return $this->entregas()->exists();
    }

    /** Auditoría de cambios de configuración: quién cambió qué y cuándo (§4.9). Nunca saldos. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('configuracion')->logOnly(['titulo', 'lugar', 'descripcion', 'modalidad', 'puntos', 'lanza_at', 'vence_at', 'cerrada_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
