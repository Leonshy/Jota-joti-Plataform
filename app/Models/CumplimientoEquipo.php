<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CumplimientoEquipo extends Model
{
    protected $table = 'cumplimientos_equipo';

    protected $fillable = ['actividad_id', 'patrulla_id', 'unidad_id', 'cumplida', 'marcado_por'];

    protected function casts(): array
    {
        return ['cumplida' => 'boolean'];
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function patrulla(): BelongsTo
    {
        return $this->belongsTo(Patrulla::class);
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class);
    }
}
