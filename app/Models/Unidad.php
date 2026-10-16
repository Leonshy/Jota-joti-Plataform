<?php

namespace App\Models;

use App\Enums\ModoRanking;
use Database\Factories\UnidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Unidad extends Model
{
    /** @use HasFactory<UnidadFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'unidades';

    protected $fillable = ['nombre', 'modo_ranking'];

    protected function casts(): array
    {
        return ['modo_ranking' => ModoRanking::class];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function patrullas(): HasMany
    {
        return $this->hasMany(Patrulla::class);
    }

    public function tieneParticipantes(): bool
    {
        return $this->usuarios()->exists() || $this->patrullas()->exists();
    }

    public function puedeBorrarse(): bool
    {
        return ! $this->tieneParticipantes()
            && ! DB::table('mision_unidad')->where('unidad_id', $this->getKey())->exists()
            && ! DB::table('actividad_unidad')->where('unidad_id', $this->getKey())->exists();
    }

    /** Auditoría de cambios de configuración: quién cambió qué y cuándo (§4.9). Nunca saldos. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('configuracion')->logOnly(['nombre', 'modo_ranking'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
