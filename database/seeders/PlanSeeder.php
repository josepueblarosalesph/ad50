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
                'precio_clp' => 0,
                'precio_uf' => 2,
                'periodo' => 'único',
                'destacado' => false,
                'features' => ['1 publicación', 'Match inteligente (candidatos que más se acercan al perfil buscado)', '5 accesos a perfiles completos o desbloqueos de CV'],
                'recomendacion' => 'Recomendado para búsquedas puntuales',
            ],
            [
                'codigo' => 'empresa_pro',
                'nombre' => 'Profesional',
                'audiencia' => 'empresa',
                'precio_clp' => 0,
                'precio_uf' => 30,
                'periodo' => 'anual',
                'destacado' => false,
                'features' => ['30 publicaciones', 'Match inteligente (candidatos que más se acercan al perfil buscado)', '15 accesos a perfiles completos o desbloqueos de CV', 'Soporte técnico'],
                'recomendacion' => 'Recomendado para múltiples búsquedas',
            ],
            [
                'codigo' => 'empresa_premium',
                'nombre' => 'Premium',
                'audiencia' => 'empresa',
                'precio_clp' => 0,
                'precio_uf' => 45,
                'periodo' => 'anual',
                'destacado' => true,
                'features' => ['Publicaciones ilimitadas', 'Match inteligente (candidatos que más se acercan al perfil buscado)', '100 accesos a perfiles completos o desbloqueos de CV', 'Soporte técnico'],
                'recomendacion' => 'Recomendado para empresas con altas demandas de ofertas laborales',
            ],
        ];
    }
}
