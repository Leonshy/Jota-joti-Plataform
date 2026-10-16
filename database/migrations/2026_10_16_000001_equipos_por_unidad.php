<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los Rovers no se arman por patrulla: cuando una actividad es «por equipo» (la estación JOTA), el equipo es toda su
 * unidad. Por eso una reserva o un cumplimiento puede pertenecer a una unidad, además de a una persona o a una patrulla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->foreignId('unidad_id')->nullable()->after('patrulla_id')->constrained('unidades');
            $table->unique(['turno_id', 'unidad_id']);
        });

        // Una reserva es de exactamente una de las tres: persona, patrulla o unidad.
        DB::statement('ALTER TABLE reservas ADD CONSTRAINT reservas_un_solo_sujeto CHECK ((user_id IS NOT NULL) + (patrulla_id IS NOT NULL) + (unidad_id IS NOT NULL) = 1)');

        Schema::table('cumplimientos_equipo', function (Blueprint $table) {
            $table->foreignId('patrulla_id')->nullable()->change();
            $table->foreignId('unidad_id')->nullable()->after('patrulla_id')->constrained('unidades');
            $table->unique(['actividad_id', 'unidad_id']); // un cumplimiento por unidad y actividad
        });

        DB::statement('ALTER TABLE cumplimientos_equipo ADD CONSTRAINT cumplimientos_un_solo_equipo CHECK ((patrulla_id IS NULL) <> (unidad_id IS NULL))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cumplimientos_equipo DROP CHECK cumplimientos_un_solo_equipo');
        Schema::table('cumplimientos_equipo', function (Blueprint $table) {
            $table->dropUnique(['actividad_id', 'unidad_id']);
            $table->dropConstrainedForeignId('unidad_id');
            $table->foreignId('patrulla_id')->nullable(false)->change();
        });

        DB::statement('ALTER TABLE reservas DROP CHECK reservas_un_solo_sujeto');
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropUnique(['turno_id', 'unidad_id']);
            $table->dropConstrainedForeignId('unidad_id');
        });
    }
};
