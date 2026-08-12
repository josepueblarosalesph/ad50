<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El plan Básico pasa a ser de pago único, contratable hasta 3 veces por empresa al año.
 *
 * Eso obliga a mover de sitio los cupos. Hasta ahora el total salía del plan
 * (`planes.desbloqueos`) y lo gastado se contaba histórico, así que volver a contratar
 * no sumaba nada: la segunda compra daba los mismos 10 desbloqueos menos los ya usados.
 * Para que comprar de nuevo tenga sentido, el cupo se acumula **en la empresa** y cada
 * pago confirmado le suma el del plan comprado.
 *
 * En publicaciones, NULL sigue significando "ilimitadas": si alguna vez se contrata un
 * plan ilimitado, la empresa queda ilimitada y ya no vuelve a un número.
 *
 * El relleno inicial copia el cupo del plan vigente de cada empresa, de modo que nadie
 * pierde lo que ya tenía ni gana de más.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table): void {
            // Se cobra una vez y no se renueva solo; la vigencia la sigue dando `periodo`.
            $table->boolean('pago_unico')->default(false)->after('periodo');
            // Cuántas veces puede contratarlo una misma empresa en 12 meses. NULL = sin tope.
            $table->unsignedTinyInteger('max_contrataciones_anuales')->nullable()->after('pago_unico');
        });

        Schema::table('empresas', function (Blueprint $table): void {
            $table->unsignedInteger('desbloqueos_cupo')->default(0)->after('plan_hasta');
            // NULL = ilimitadas, y por eso el default es 0 y no NULL: quien no ha
            // contratado nada tiene cero publicaciones, no infinitas. Solo el relleno de
            // abajo (o una compra de un plan ilimitado) puede dejarlo en NULL.
            $table->unsignedInteger('publicaciones_cupo')->nullable()->default(0)->after('desbloqueos_cupo');
        });

        $this->rellenarCupos();

        DB::table('planes')->where('codigo', 'empresa_basic')->update([
            'pago_unico' => true,
            'max_contrataciones_anuales' => 3,
        ]);
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->dropColumn(['desbloqueos_cupo', 'publicaciones_cupo']);
        });

        Schema::table('planes', function (Blueprint $table): void {
            $table->dropColumn(['pago_unico', 'max_contrataciones_anuales']);
        });
    }

    /**
     * Cada empresa arranca con el cupo de su plan actual: es exactamente lo que el
     * cálculo anterior le concedía, así que el cambio no altera lo que ya tenía.
     * Sin plan asignado quedan en 0 desbloqueos y 0 publicaciones, que es lo mismo que
     * devolvía antes `plan?->desbloqueos ?? 0`.
     */
    private function rellenarCupos(): void
    {
        // SQL en crudo con UPDATE ... FROM, que es la forma de PostgreSQL: el
        // `join()->update()` del query builder genera sintaxis de MySQL y aquí falla.
        DB::statement(<<<'SQL'
            UPDATE empresas
               SET desbloqueos_cupo   = coalesce(planes.desbloqueos, 0),
                   publicaciones_cupo = planes.publicaciones
              FROM planes
             WHERE planes.id = empresas.plan_id
        SQL);
    }
};
