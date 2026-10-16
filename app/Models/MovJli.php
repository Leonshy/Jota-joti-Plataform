<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovJli extends Movimiento
{
    protected $table = 'mov_jlis';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
