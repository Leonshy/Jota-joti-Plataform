<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('misiones', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion');
            $table->string('modalidad'); // App\Enums\Modalidad
            $table->unsignedInteger('puntos');
            $table->dateTime('lanza_at')->index();
            $table->dateTime('vence_at')->nullable();
            $table->dateTime('cerrada_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mision_unidad', function (Blueprint $table) {
            $table->foreignId('mision_id')->constrained('misiones')->cascadeOnDelete();
            $table->foreignId('unidad_id')->constrained('unidades');
            $table->primary(['mision_id', 'unidad_id']);
        });

        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mision_id')->constrained('misiones');
            $table->foreignId('user_id')->constrained('users'); // quien entrega
            $table->foreignId('patrulla_id')->nullable()->constrained('patrullas');
            $table->text('texto')->nullable();
            $table->string('estado')->default('pendiente'); // App\Enums\EstadoEntrega
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users');
            $table->dateTime('revisado_at')->nullable();
            // Idempotencia en base de datos (CLAUDE.md §2.3): solo puede existir UNA entrega aprobada
            // por misión y por sujeto (la persona en misiones individuales, la patrulla en las de
            // patrulla). Un segundo UPDATE a "aprobada" falla con violación de unicidad.
            $table->string('clave_aprobacion')->nullable()->storedAs(
                "CASE WHEN estado = 'aprobada' THEN CONCAT(mision_id, ':', IFNULL(CONCAT('p', patrulla_id), CONCAT('u', user_id))) ELSE NULL END"
            );
            $table->timestamps();

            $table->unique('clave_aprobacion');
            $table->index(['mision_id', 'user_id', 'estado']);
            $table->index(['mision_id', 'patrulla_id', 'estado']);
            $table->index(['estado', 'created_at']);
        });

        Schema::create('entrega_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas')->cascadeOnDelete();
            $table->string('ruta'); // disco "private"; antes de procesarse apunta al original en tmp/
            $table->dateTime('procesada_at')->nullable(); // solo se sirve si ya fue re-codificada
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrega_imagenes');
        Schema::dropIfExists('entregas');
        Schema::dropIfExists('mision_unidad');
        Schema::dropIfExists('misiones');
    }
};
