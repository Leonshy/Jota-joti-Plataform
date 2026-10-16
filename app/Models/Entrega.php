<?php

namespace App\Models;

use App\Enums\EstadoEntrega;
use Database\Factories\EntregaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entrega extends Model
{
    /** @use HasFactory<EntregaFactory> */
    use HasFactory;

    protected $fillable = ['mision_id', 'user_id', 'patrulla_id', 'texto', 'estado'];

    protected function casts(): array
    {
        return [
            'estado' => EstadoEntrega::class,
            'revisado_at' => 'datetime',
        ];
    }

    public function mision(): BelongsTo
    {
        return $this->belongsTo(Mision::class);
    }

    /** Quien entrega. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patrulla(): BelongsTo
    {
        return $this->belongsTo(Patrulla::class);
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(EntregaImagen::class);
    }

    public function scopePendientes(Builder $query): void
    {
        $query->where('estado', EstadoEntrega::Pendiente);
    }
}
