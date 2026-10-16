<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // App\Enums\TipoActividad
            $table->string('titulo');
            $table->string('modalidad'); // App\Enums\Modalidad (patrulla = por equipo)
            $table->unsignedInteger('puntos');
            $table->timestamps();
        });

        Schema::create('actividad_unidad', function (Blueprint $table) {
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->foreignId('unidad_id')->constrained('unidades');
            $table->primary(['actividad_id', 'unidad_id']);
        });

        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->dateTime('inicio');
            $table->dateTime('fin');
            $table->unsignedSmallInteger('cupo')->nullable();
            $table->timestamps();

            $table->index(['actividad_id', 'inicio']);
        });

        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('patrulla_id')->nullable()->constrained('patrullas');
            $table->timestamps();

            $table->unique(['turno_id', 'user_id']);
            $table->unique(['turno_id', 'patrulla_id']);
        });

        Schema::create('sesiones_asistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades');
            $table->foreignId('turno_id')->nullable()->constrained('turnos');
            $table->foreignId('abierta_por')->constrained('users');
            $table->string('secreto', 64); // clave HMAC del QR rotativo (hex)
            $table->dateTime('abierta_at');
            $table->dateTime('cerrada_at')->nullable();
            // Una sola sesión abierta por actividad, garantizada por la base (cerrada_at nulo = abierta).
            $table->unsignedBigInteger('abierta_unica')->nullable()->storedAs('CASE WHEN cerrada_at IS NULL THEN actividad_id ELSE NULL END');
            $table->timestamps();

            $table->unique('abierta_unica');
        });

        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('sesion_id')->constrained('sesiones_asistencia');
            $table->timestamps();

            $table->unique(['actividad_id', 'user_id']); // una asistencia por persona y actividad
        });

        Schema::create('cumplimientos_equipo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades');
            $table->foreignId('patrulla_id')->constrained('patrullas');
            $table->boolean('cumplida');
            $table->foreignId('marcado_por')->constrained('users');
            $table->timestamps();

            $table->unique(['actividad_id', 'patrulla_id']); // un cumplimiento por patrulla y actividad
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cumplimientos_equipo');
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('sesiones_asistencia');
        Schema::dropIfExists('reservas');
        Schema::dropIfExists('turnos');
        Schema::dropIfExists('actividad_unidad');
        Schema::dropIfExists('actividades');
    }
};
