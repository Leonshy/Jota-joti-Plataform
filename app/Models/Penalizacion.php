<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penalizacion extends Model
{
    protected $table = 'penalizaciones';

    protected $fillable = ['user_id', 'patrulla_id', 'cantidad', 'motivo', 'registrado_por'];

    protected function casts(): array
    {
        return ['cantidad' => 'integer', 'anulada_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patrulla(): BelongsTo
    {
        return $this->belongsTo(Patrulla::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function estaAnulada(): bool
    {
        return $this->anulada_at !== null;
    }

    /** La unidad sobre la que se actuó, para decidir quién puede gestionarla. */
    public function unidadId(): ?int
    {
        return $this->user_id ? $this->user->unidad_id : $this->patrulla->unidad_id;
    }
}
