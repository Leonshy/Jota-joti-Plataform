<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('modo_ranking'); // App\Enums\ModoRanking
            $table->timestamps();
        });

        Schema::create('patrullas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('grupo_id')->constrained('grupos');
            $table->foreignId('unidad_id')->constrained('unidades');
            $table->integer('puntos')->default(0); // saldo cacheado; puede ser negativo (D14)
            $table->timestamps();

            $table->unique(['unidad_id', 'grupo_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrullas');
        Schema::dropIfExists('unidades');
        Schema::dropIfExists('grupos');
    }
};
