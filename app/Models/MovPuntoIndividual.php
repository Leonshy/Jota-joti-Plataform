<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovPuntoIndividual extends Movimiento
{
    protected $table = 'mov_puntos_individuales';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
