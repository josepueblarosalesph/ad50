<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $table) {
            $table->string('rut')->nullable()->after('user_id');
            $table->unsignedSmallInteger('anio_nacimiento')->nullable()->after('rut');
            $table->string('linkedin')->nullable()->after('telefono');
            $table->string('carrera')->nullable()->after('cargo_actual');
            $table->string('universidad')->nullable()->after('carrera');
            $table->string('especialidad')->nullable()->after('universidad');
            $table->string('postgrado')->nullable()->after('especialidad');
            $table->string('industria_2')->nullable()->after('industria');
            $table->string('industria_3')->nullable()->after('industria_2');
            $table->string('empresa_actual')->nullable()->after('anios_experiencia');
            $table->string('experiencia_area')->nullable()->after('empresa_actual');
            $table->unsignedSmallInteger('experiencia_inicio')->nullable()->after('experiencia_area');
            $table->unsignedSmallInteger('experiencia_fin')->nullable()->after('experiencia_inicio');
            $table->text('resumen_profesional')->nullable()->after('experiencia_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postulantes', function (Blueprint $table) {
            $table->dropColumn([
                'rut',
                'anio_nacimiento',
                'linkedin',
                'carrera',
                'universidad',
                'especialidad',
                'postgrado',
                'industria_2',
                'industria_3',
                'empresa_actual',
                'experiencia_area',
                'experiencia_inicio',
                'experiencia_fin',
                'resumen_profesional',
            ]);
        });
    }
};
