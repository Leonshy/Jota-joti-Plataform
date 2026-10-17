<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de contactos JOTA (radio, suman a la patrulla) y JOTI (internet, suman a la persona), validados por el
 * dirigente de la unidad o el admin. Una sola tabla: los campos de cada tipo van nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contactos', function (Blueprint $t) {
            $t->id();
            $t->string('tipo', 10); // App\Enums\TipoContacto
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('unidad_id')->constrained('unidades');
            // JOTA: la patrulla suma (o, en los Rovers, la unidad entera); JOTI: la persona.
            $t->foreignId('patrulla_id')->nullable()->constrained('patrullas');
            $t->boolean('es_local'); // Paraguay (JOTI: por el país; JOTA: por el indicativo ZP…)
            $t->string('estado', 12)->default('pendiente'); // App\Enums\EstadoEntrega
            $t->unsignedInteger('puntos')->nullable(); // lo que se acreditó al aprobar
            $t->foreignId('revisado_por')->nullable()->constrained('users');
            $t->dateTime('revisado_at')->nullable();
            $t->text('motivo_rechazo')->nullable();

            // JOTI: datos del scout con quien se habló y captura de la conversación.
            $t->string('grupo_scout', 120)->nullable();
            $t->string('ciudad', 120)->nullable();
            $t->string('unidad_scout', 120)->nullable();
            $t->string('pais', 80)->nullable();
            $t->string('captura_ruta')->nullable();
            $t->dateTime('captura_procesada_at')->nullable();

            // JOTA: libro de guardia.
            $t->dateTime('contactado_at')->nullable();
            $t->string('estacion', 20)->nullable();
            $t->string('rst_tx', 8)->nullable();
            $t->string('rst_rx', 8)->nullable();
            $t->string('frecuencia', 16)->nullable();
            $t->string('emision', 12)->nullable();
            $t->string('obs', 120)->nullable();
            $t->boolean('qsl_r')->default(false);
            $t->boolean('qsl_e')->default(false);

            $t->timestamps();

            $t->index(['unidad_id', 'estado']);
            $t->index(['tipo', 'user_id']);
            $t->index(['tipo', 'patrulla_id']);
        });

        // Ajustes del evento que cambia el admin (hoy: puntaje de los contactos). Ausente = sin configurar.
        Schema::create('ajustes', function (Blueprint $t) {
            $t->id();
            $t->string('clave', 80)->unique();
            $t->string('valor');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
        Schema::dropIfExists('contactos');
    }
};
