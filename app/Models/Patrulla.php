<?php

namespace App\Models;

use Database\Factories\PatrullaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Patrulla extends Model
{
    /** @use HasFactory<PatrullaFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'patrullas';

    protected $fillable = ['nombre', 'grupo_id', 'unidad_id'];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class);
    }

    public function integrantes(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Solo una patrulla sin historia (integrantes, puntos, entregas, reservas) se puede borrar. */
    public function puedeBorrarse(): bool
    {
        return ! $this->integrantes()->exists()
            && ! DB::table('mov_puntos_patrulla')->where('patrulla_id', $this->getKey())->exists()
            && ! DB::table('entregas')->where('patrulla_id', $this->getKey())->exists()
            && ! DB::table('reservas')->where('patrulla_id', $this->getKey())->exists()
            && ! DB::table('cumplimientos_equipo')->where('patrulla_id', $this->getKey())->exists()
            && ! DB::table('penalizaciones')->where('patrulla_id', $this->getKey())->exists();
    }

    /** Auditoría de cambios de configuración: quién cambió qué y cuándo (§4.9). Nunca saldos. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('configuracion')->logOnly(['nombre', 'grupo_id', 'unidad_id'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
