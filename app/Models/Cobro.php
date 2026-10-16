<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cobro extends Model
{
    public const PENDIENTE = 'pendiente';

    public const PAGADO = 'pagado';

    public const VENCIDO = 'vencido';

    protected $table = 'cobros';

    protected $fillable = ['token', 'monto', 'creado_por', 'vence_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['monto' => 'integer', 'vence_at' => 'datetime', 'pagado_at' => 'datetime'];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function pagador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }

    /** pendiente | pagado | vencido, con la hora del servidor. Un cobro pagado nunca figura vencido. */
    public function estado(): string
    {
        return match (true) {
            $this->pagado_at !== null => self::PAGADO,
            $this->vence_at->lte(now()) => self::VENCIDO,
            default => self::PENDIENTE,
        };
    }

    public function segundosRestantes(): int
    {
        return max(0, (int) ceil(now()->diffInSeconds($this->vence_at, false)));
    }
}
