<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asocia un candidato encontrado en el Talent Finder a una o más publicaciones.
     *
     * La llave es (publicacion_id, postulante_id) y NO la fila de busqueda_candidato:
     * el motor de matching borra esas filas cuando el candidato deja de cumplir los
     * criterios, y eso no debe arrastrarse la asociación que la empresa hizo a mano.
     * `busqueda_id` queda solo como trazabilidad de dónde salió el candidato.
     */
    public function up(): void
    {
        Schema::create('publicacion_candidato', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->foreignId('postulante_id')->constrained('postulantes')->cascadeOnDelete();
            $table->foreignId('busqueda_id')->nullable()->constrained('busquedas')->nullOnDelete();
            $table->timestamps();

            $table->unique(['publicacion_id', 'postulante_id']);
            $table->index('postulante_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicacion_candidato');
    }
};
