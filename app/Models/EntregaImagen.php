<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntregaImagen extends Model
{
    protected $table = 'entrega_imagenes';

    protected $fillable = ['entrega_id', 'ruta', 'procesada_at'];

    protected function casts(): array
    {
        return ['procesada_at' => 'datetime'];
    }

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(Entrega::class);
    }
}
