<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La plataforma deja de cobrarle a las empresas: se crea el plan ilimitado —sin costo,
 * con cupo que no se agota y 50 años de vigencia— y se le asigna a todas las empresas que
 * ya existen.
 *
 * El backfill no es un extra: las pantallas de planes quedan ocultas (ver
 * Funcionalidades::cobroAEmpresas()), así que una empresa con el plan vencido se quedaría
 * sin la única salida que tenía. De aquí en adelante el plan lo concede el registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        $plan = Plan::ilimitado();

        DB::table('empresas')->update([
            'plan_id' => $plan->id,
            'plan_hasta' => now()->addYears(Plan::ANIOS_ILIMITADO)->toDateString(),
            'desbloqueos_cupo' => Plan::CUPO_ILIMITADO,
            'publicaciones_cupo' => null, // Ilimitadas.
        ]);
    }

    /**
     * Deja sin plan a las empresas que quedaron con el ilimitado. No devuelve el que cada
     * una tenía antes: esa asignación no se guardó en ninguna parte (los pagos sí, en
     * `pagos`).
     */
    public function down(): void
    {
        $planId = DB::table('planes')->where('codigo', Plan::CODIGO_ILIMITADO)->value('id');

        if ($planId === null) {
            return;
        }

        DB::table('empresas')
            ->where('plan_id', $planId)
            ->update(['plan_id' => null, 'plan_hasta' => null, 'desbloqueos_cupo' => 0, 'publicaciones_cupo' => 0]);

        DB::table('planes')->where('id', $planId)->delete();
    }
};
