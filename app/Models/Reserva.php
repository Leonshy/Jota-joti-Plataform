<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = ['turno_id', 'user_id', 'patrulla_id', 'unidad_id'];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
