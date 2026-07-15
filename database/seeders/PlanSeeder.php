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
                'codigo' => 'empresa_basic',
                'nombre' => 'Básico',
                'audiencia' => 'empresa',
                'precio_clp' => 0,
                'precio_uf' => 5,
                'periodo' => 'anual',
                'destacado' => false,
                'features' => ['5 publicaciones', 'Match inteligente', '10 desbloqueos de perfiles'],
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
                'features' => ['30 publicaciones', 'Match inteligente', '50 desbloqueos de perfiles', 'Soporte técnico'],
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
                'features' => ['Publicaciones ilimitadas', 'Match inteligente', '100 desbloqueos de perfiles', 'Soporte técnico'],
                'recomendacion' => 'Recomendado para empresas con alto volumen de publicaciones.',
            ],
        ];
    }
}
