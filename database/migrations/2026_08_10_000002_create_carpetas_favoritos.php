<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carpetas para agrupar los candidatos guardados.
 *
 * La carpeta es de **cada usuario** del equipo (`user_id`), no de la empresa: los
 * favoritos son compartidos —cualquiera del equipo ve la estrella que marcó otro— pero
 * cada reclutador arma sus propias agrupaciones sobre ellos, igual que las notas de
 * candidato. `empresa_id` queda para acotar por cuenta y para que la carpeta muera con
 * la empresa aunque el usuario siga existiendo.
 *
 * La relación con el favorito es de muchos a muchos: un mismo candidato puede estar en
 * varias carpetas a la vez (sirve para más de un proceso). Al quitar la estrella se cae
 * el favorito y con él sus filas del pivote, así que una carpeta nunca queda apuntando a
 * alguien que ya no está guardado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpetas_favoritos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nombre', 40);
            $table->timestamps();

            // Dos carpetas con el mismo nombre para la misma persona serían indistinguibles.
            $table->unique(['user_id', 'nombre']);
            $table->index('empresa_id');
        });

        Schema::create('carpeta_favorito', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carpeta_id')->constrained('carpetas_favoritos')->cascadeOnDelete();
            $table->foreignId('favorito_id')->constrained('favoritos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['carpeta_id', 'favorito_id']);
            $table->index('favorito_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpeta_favorito');
        Schema::dropIfExists('carpetas_favoritos');
    }
};
