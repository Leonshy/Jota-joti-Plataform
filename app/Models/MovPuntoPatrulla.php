<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovPuntoPatrulla extends Movimiento
{
    protected $table = 'mov_puntos_patrulla';

    public function patrulla(): BelongsTo
    {
        return $this->belongsTo(Patrulla::class);
    }
}
