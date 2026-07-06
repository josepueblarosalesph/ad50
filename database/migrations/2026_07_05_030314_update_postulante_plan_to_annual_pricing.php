<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('planes')
            ->where('audiencia', 'postulante')
            ->update([
                'precio_clp' => 20000,
                'periodo' => 'anual',
                'features' => json_encode([
                    'Perfil visible en el portal',
                    'Acceso a tus coincidencias',
                    'Oportunidades de empresas asociadas',
                    'Soporte por email',
                ], JSON_THROW_ON_ERROR),
            ]);
    }

    public function down(): void
    {
        DB::table('planes')
            ->where('audiencia', 'postulante')
            ->update([
                'precio_clp' => 9900,
                'periodo' => 'único',
                'features' => json_encode([
                    'Ficha siempre visible',
                    'Sin renovación',
                    'Acceso a tus matches',
                    'Soporte por email',
                ], JSON_THROW_ON_ERROR),
            ]);
    }
};
