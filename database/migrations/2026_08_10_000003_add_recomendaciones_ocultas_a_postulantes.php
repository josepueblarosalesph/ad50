<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite cerrar el aviso de recomendaciones de Oportunidades.
 *
 * Se guarda el **porcentaje de completitud que tenía la ficha al cerrarlo**, no una
 * fecha ni un booleano: el aviso vuelve a aparecer en cuanto ese porcentaje suba, es
 * decir en cuanto la persona complete algo. Como todo ítem pesa más que cero, cualquier
 * dato que agregue mueve el número, así que sirve de marca exacta de "ya avanzó".
 *
 * Null = nunca lo cerró.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('recomendaciones_ocultas_hasta')
                ->nullable()
                ->after('completitud');
        });
    }

    public function down(): void
    {
        Schema::table('postulantes', function (Blueprint $table): void {
            $table->dropColumn('recomendaciones_ocultas_hasta');
        });
    }
};
