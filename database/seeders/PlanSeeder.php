<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->planes() as $plan) {
            Plan::query()->updateOrCreate(['codigo' => $plan['codigo']], $plan);
        }
    }

    /** @return list<array<string, mixed>> */
    private function planes(): array
    {
        return [
            [
                'codigo' => 'postulante',
                'nombre' => 'Postulante visible',
                'audiencia' => 'postulante',
                'precio_clp' => 20000,
                'periodo' => 'anual',
                'features' => ['Perfil visible en el portal', 'Acceso a tus coincidencias', 'Oportunidades de empresas asociadas', 'Soporte por email'],
            ],
            [
                'codigo' => 'empresa_basic',
                'nombre' => 'Básico',
                'audiencia' => 'empresa',
                'precio_clp' => 89000,
                'periodo' => 'mensual',
                'features' => ['1 búsqueda activa', '10 contactos por mes', 'Filtros estándar'],
            ],
            [
                'codigo' => 'empresa_pro',
                'nombre' => 'Profesional',
                'audiencia' => 'empresa',
                'precio_clp' => 189000,
                'periodo' => 'mensual',
                'destacado' => true,
                'features' => ['Búsquedas ilimitadas', 'Contactos ilimitados', 'Filtros avanzados', 'Soporte dedicado'],
            ],
        ];
    }
}
