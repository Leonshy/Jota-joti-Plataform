<?php

namespace App\Models;

use Database\Factories\GrupoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Grupo extends Model
{
    /** @use HasFactory<GrupoFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'grupos';

    protected $fillable = ['nombre'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function patrullas(): HasMany
    {
        return $this->hasMany(Patrulla::class);
    }

    /** Un grupo con participantes o patrullas no se borra (FK y datos del evento). */
    public function puedeBorrarse(): bool
    {
        return ! $this->usuarios()->exists() && ! $this->patrullas()->exists();
    }

    /** Auditoría de cambios de configuración: quién cambió qué y cuándo (§4.9). Nunca saldos. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('configuracion')->logOnly(['nombre'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
