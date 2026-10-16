<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Registro contable de solo agregar (CLAUDE.md §2.1). Una corrección es un movimiento nuevo
 * de signo contrario, con motivo.
 */
abstract class Movimiento extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los movimientos no se editan.'));
        static::deleting(fn () => throw new LogicException('Los movimientos no se borran.'));
    }

    protected function casts(): array
    {
        return ['cantidad' => 'integer'];
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
