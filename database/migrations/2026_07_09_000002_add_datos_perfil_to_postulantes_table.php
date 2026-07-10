<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $t) {
            $t->string('nacionalidad')->nullable()->after('genero');
            $t->string('sitio_web')->nullable()->after('linkedin');
            $t->string('situacion_laboral')->nullable()->after('modalidad_trabajo');
            $t->unsignedInteger('expectativa_renta')->nullable()->after('situacion_laboral');
        });
    }

    public function down(): void
    {
        Schema::table('postulantes', function (Blueprint $t) {
            $t->dropColumn(['nacionalidad', 'sitio_web', 'situacion_laboral', 'expectativa_renta']);
        });
    }
};
