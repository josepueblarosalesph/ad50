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
        Schema::create('publicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('cargo', 100);
            $table->string('tipo_cargo');
            $table->unsignedSmallInteger('vacantes')->default(1);
            $table->string('nombre_empresa');
            $table->text('descripcion');
            $table->string('modalidad');
            $table->string('pais')->default('Chile');
            $table->string('comuna');
            $table->string('actividad_empresa');
            $table->string('jerarquia');
            $table->unsignedInteger('sueldo')->nullable();
            $table->boolean('mostrar_sueldo')->default(false);
            $table->text('requisitos');
            $table->string('experiencia_laboral');
            $table->string('estudios_minimos');
            $table->string('situacion_academica');
            $table->json('competencias')->nullable();
            $table->json('idiomas')->nullable();
            $table->json('preguntas')->nullable();
            $table->boolean('empleo_inclusivo')->default(false);
            $table->boolean('postulacion_facil')->default(true);
            $table->boolean('notificar_postulaciones')->default(true);
            $table->boolean('evaluacion_online')->default(false);
            $table->boolean('evaluacion_manual')->default(false);
            $table->unsignedSmallInteger('vigencia_dias')->default(30);
            $table->date('vigente_hasta')->index();
            $table->string('estado')->default('publicada')->index();
            $table->timestamps();

            $table->index(['empresa_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publicaciones');
    }
};
