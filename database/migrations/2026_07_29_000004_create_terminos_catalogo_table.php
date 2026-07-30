<?php

use Database\Seeders\TerminoCatalogoSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los catálogos profesionales (industrias, cargos, regiones, …) pasan de vivir como
 * arreglos en PHP a ser datos, para poder administrarlos desde el panel de admin.
 *
 * La carga inicial la hace TerminoCatalogoSeeder a partir de esos mismos arreglos, que
 * se conservan en el código como valores por defecto: si la tabla está vacía,
 * CatalogosProfesionales sigue leyéndolos y la plataforma funciona igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminos_catalogo', function (Blueprint $table): void {
            $table->id();
            // Clave del catálogo (industria, cargo, region, …). Ver CatalogosAdministrables.
            $table->string('catalogo', 40);
            $table->string('valor', 190);
            // El orden importa: varios catálogos no son alfabéticos (Chile primero en
            // países, "Nacional"/"Internacional" al comienzo de las regiones…).
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['catalogo', 'valor']);
            $table->index(['catalogo', 'orden']);
        });

        // Carga inicial desde los arreglos del código, para que el panel arranque con
        // todo lo que la plataforma ya ofrecía.
        (new TerminoCatalogoSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('terminos_catalogo');
    }
};
