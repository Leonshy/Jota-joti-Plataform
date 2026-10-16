<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Registros contables de solo agregar (CLAUDE.md §2.1): sin updated_at, nunca se editan.
    public function up(): void
    {
        Schema::create('mov_puntos_individuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->integer('cantidad');
            $table->nullableMorphs('origen');
            $table->string('motivo')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('mov_puntos_patrulla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrulla_id')->constrained('patrullas');
            $table->integer('cantidad');
            $table->nullableMorphs('origen');
            $table->string('motivo')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['patrulla_id', 'created_at']);
        });

        Schema::create('mov_jlis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->integer('cantidad');
            $table->nullableMorphs('origen');
            $table->string('motivo')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        // Penalizaciones (§4.5): apartado propio, con motivo obligatorio. Afectan solo puntos y se
        // aplican a una persona o a una patrulla (exactamente una de las dos). Anular una es un
        // movimiento de signo contrario en el ledger; acá solo queda la marca.
        Schema::create('penalizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('patrulla_id')->nullable()->constrained('patrullas');
            $table->unsignedInteger('cantidad');
            $table->string('motivo');
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamp('anulada_at')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('users');
            $table->string('motivo_anulacion')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['patrulla_id', 'created_at']);
        });
        DB::statement('ALTER TABLE penalizaciones ADD CONSTRAINT penalizaciones_un_solo_objetivo CHECK ((user_id IS NULL) <> (patrulla_id IS NULL))');

        Schema::create('cobros', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->unsignedInteger('monto');
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamp('vence_at');
            $table->foreignId('pagado_por')->nullable()->constrained('users');
            $table->timestamp('pagado_at')->nullable();
            $table->timestamps();

            $table->index(['creado_por', 'created_at']);
        });

        // Devoluciones de la tienda: registro propio (quién, a quién, cuánto y por qué). El ledger
        // de LisCoins las referencia como origen del movimiento.
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedInteger('monto');
            $table->string('motivo');
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();

            $table->index(['registrado_por', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devoluciones');
        Schema::dropIfExists('penalizaciones');
        Schema::dropIfExists('cobros');
        Schema::dropIfExists('mov_jlis');
        Schema::dropIfExists('mov_puntos_patrulla');
        Schema::dropIfExists('mov_puntos_individuales');
    }
};
