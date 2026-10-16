<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada taller, estación y misión tiene un lugar donde se hace. Es nullable en la base solo para no romper lo ya cargado;
 * los formularios del panel lo piden siempre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', fn (Blueprint $t) => $t->string('lugar', 120)->nullable()->after('titulo'));
        Schema::table('misiones', fn (Blueprint $t) => $t->string('lugar', 120)->nullable()->after('titulo'));
    }

    public function down(): void
    {
        Schema::table('actividades', fn (Blueprint $t) => $t->dropColumn('lugar'));
        Schema::table('misiones', fn (Blueprint $t) => $t->dropColumn('lugar'));
    }
};
